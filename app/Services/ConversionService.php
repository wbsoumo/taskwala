<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Conversion;
use App\Models\ConversionStatusHistory;
use App\Models\PayoutSnapshot;
use App\Models\PostbackProvider;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConversionService
{
    protected LedgerService $ledgerService;
    protected ReferralService $referralService;

    public function __construct(LedgerService $ledgerService, ReferralService $referralService)
    {
        $this->ledgerService = $ledgerService;
        $this->referralService = $referralService;
    }

    /**
     * Ingest conversion from verified postback or manual admin action.
     */
    public function processConversion(
        string $clickId,
        ?PostbackProvider $provider = null,
        ?string $providerConversionId = null,
        string $status = 'approved',
        ?string $requestIp = null,
        ?string $userAgent = null
    ): array {
        // 1. Resolve Click
        /** @var Click $click */
        $click = Click::with(['campaign', 'user', 'link'])
            ->where('click_id', $clickId)
            ->first();

        if (!$click) {
            throw new InvalidArgumentException("Click ID not found: {$clickId}");
        }

        // 2. Duplicate Conversion Protection (Idempotency)
        $existingConversion = Conversion::where('click_id', $clickId)
            ->when($providerConversionId, function ($query, $txId) {
                return $query->orWhere('provider_conversion_id', $txId);
            })->first();

        if ($existingConversion) {
            // Already processed - return existing conversion (Idempotent success)
            return [
                'conversion' => $existingConversion,
                'is_duplicate' => true,
            ];
        }

        // Map status input to valid conversion status
        $normalizedStatus = match ($status) {
            'approved', 'success', 'complete', 'completed' => 'approved',
            'pending' => 'pending',
            'rejected', 'declined' => 'rejected',
            default => 'approved',
        };

        return DB::transaction(function () use ($click, $provider, $providerConversionId, $normalizedStatus, $requestIp, $userAgent) {
            // 3. Create Master Conversion Record
            $conversion = Conversion::create([
                'click_id' => $click->click_id,
                'campaign_id' => $click->campaign_id,
                'user_id' => $click->user_id,
                'link_id' => $click->link_id,
                'postback_provider_id' => $provider?->id,
                'provider_conversion_id' => $providerConversionId,
                'status' => $normalizedStatus,
                'conversion_time' => now(),
                'ip_address' => $requestIp ?? $click->ip_address,
                'user_agent' => $userAgent ?? $click->user_agent,
            ]);

            // Mark click status as converted and link any existing CustomerPayout record
            $click->update(['status' => 'converted']);
            $click->link->increment('conversion_count');
            
            \App\Models\CustomerPayout::where('click_id', $click->click_id)
                ->whereNull('conversion_id')
                ->update(['conversion_id' => $conversion->id]);

            // 4. Freeze Immutable Payout Snapshot
            $advertiserPayout = (float) $click->campaign->advertiser_payout;
            $affiliateAllocatedPayout = (float) $click->allocated_affiliate_payout;
            $customerPayout = (float) $click->customer_payout;
            $affiliateCommission = (float) $click->affiliate_commission;
            $platformMargin = $advertiserPayout - $affiliateAllocatedPayout;

            PayoutSnapshot::create([
                'conversion_id' => $conversion->id,
                'advertiser_payout' => $advertiserPayout,
                'affiliate_allocated_payout' => $affiliateAllocatedPayout,
                'customer_payout' => $customerPayout,
                'affiliate_commission' => $affiliateCommission,
                'platform_margin' => $platformMargin,
                'currency' => $click->campaign->currency ?? 'INR',
                'created_at' => now(),
            ]);

            // 5. Create Conversion Status History
            ConversionStatusHistory::create([
                'conversion_id' => $conversion->id,
                'previous_status' => null,
                'new_status' => $normalizedStatus,
                'changed_by_type' => $provider ? 'postback' : 'system',
                'reason' => 'Initial conversion ingestion.',
                'created_at' => now(),
            ]);

            // 6. Process Financial Earning in Ledger if Approved
            if ($normalizedStatus === 'approved') {
                if ($affiliateCommission > 0) {
                    $this->ledgerService->recordTransaction(
                        user: $click->user,
                        type: 'affiliate_commission',
                        direction: 'credit',
                        amount: $affiliateCommission,
                        conversion: $conversion,
                        reference: 'CONV_' . $conversion->public_id,
                        description: "Affiliate commission earned for campaign {$click->campaign->name}"
                    );
                }
                // Process Referral Reward
                $this->referralService->processConversionReferralReward($conversion);
            } elseif ($normalizedStatus === 'pending') {
                if ($affiliateCommission > 0) {
                    $this->ledgerService->addPendingBalance($click->user, $affiliateCommission);
                }
            }

            AuditService::log(
                action: 'conversion_created',
                entityType: Conversion::class,
                entityId: $conversion->id,
                newValues: [
                    'conversion_id' => $conversion->id,
                    'status' => $normalizedStatus,
                    'affiliate_commission' => $affiliateCommission,
                    'customer_payout' => $customerPayout,
                ],
                actorType: $provider ? 'system' : 'admin'
            );

            return [
                'conversion' => $conversion,
                'is_duplicate' => false,
            ];
        });
    }

    /**
     * Admin manual status update for a conversion with full audit log & ledger adjustment.
     */
    public function updateStatus(Conversion $conversion, string $newStatus, string $reason = '', ?int $adminId = null): Conversion
    {
        $oldStatus = $conversion->status;
        if ($oldStatus === $newStatus) {
            return $conversion;
        }

        return DB::transaction(function () use ($conversion, $oldStatus, $newStatus, $reason, $adminId) {
            $conversion->status = $newStatus;
            $conversion->save();

            // Record status history
            ConversionStatusHistory::create([
                'conversion_id' => $conversion->id,
                'previous_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by_type' => 'admin',
                'changed_by_id' => $adminId,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            $snapshot = $conversion->payoutSnapshot;
            $commission = $snapshot ? (float) $snapshot->affiliate_commission : 0.00;

            // Financial Ledger Adjustments
            if ($oldStatus !== 'approved' && $newStatus === 'approved' && $commission > 0) {
                // Credit wallet
                $this->ledgerService->recordTransaction(
                    user: $conversion->user,
                    type: 'affiliate_commission',
                    direction: 'credit',
                    amount: $commission,
                    conversion: $conversion,
                    reference: 'MANUAL_APPROVE_' . $conversion->id,
                    description: "Manual conversion approval by admin."
                );
                // Process Referral Reward
                $this->referralService->processConversionReferralReward($conversion);
            } elseif ($oldStatus === 'approved' && in_array($newStatus, ['rejected', 'reversed', 'cancelled']) && $commission > 0) {
                // Debit/Reverse wallet
                $this->ledgerService->recordTransaction(
                    user: $conversion->user,
                    type: 'payout_reversal',
                    direction: 'debit',
                    amount: $commission,
                    conversion: $conversion,
                    reference: 'REVERSAL_' . $conversion->id,
                    description: "Conversion reversal by admin. Reason: {$reason}"
                );
                // Reverse Referral Reward
                $this->referralService->reverseConversionReferralReward($conversion);
            }

            AuditService::log(
                action: 'conversion_status_updated',
                entityType: Conversion::class,
                entityId: $conversion->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus, 'reason' => $reason],
                actorType: 'admin',
                actorId: $adminId
            );

            return $conversion;
        });
    }
}
