<?php

namespace App\Services;

use App\Models\Conversion;
use App\Models\ReferralEarning;
use App\Models\ReferralRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferralService
{
    protected LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Evaluate and process referral rewards upon conversion approval.
     */
    public function processConversionReferralReward(Conversion $conversion): ?ReferralEarning
    {
        if (!Schema::hasTable('referral_rules') || !Schema::hasTable('referral_earnings')) {
            return null;
        }

        /** @var User $referredUser */
        $referredUser = User::find($conversion->user_id);
        if (!$referredUser || !$referredUser->referred_by) {
            return null; // User was not referred by anyone
        }

        /** @var User $referrer */
        $referrer = User::find($referredUser->referred_by);
        if (!$referrer || !$referrer->isActive()) {
            return null;
        }

        // Find active matching referral rule with highest priority
        $rule = ReferralRule::where('status', 'active')
            ->where(function ($query) use ($conversion) {
                $query->where('campaign_id', $conversion->campaign_id)
                      ->orWhereNull('campaign_id');
            })
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (!$rule) {
            // Default fallback rule if no custom rule configured
            $rewardType = 'percentage';
            $rewardValue = 10.00; // Default 10%
            $ruleVersion = 1;
            $ruleId = null;
        } else {
            $rewardType = $rule->reward_type;
            $rewardValue = (float) $rule->reward_value;
            $ruleVersion = $rule->version;
            $ruleId = $rule->id;

            // Check first conversion condition if applicable
            if ($rule->condition_type === 'first_approved_conversion') {
                $previousApprovedCount = Conversion::where('user_id', $referredUser->id)
                    ->where('status', 'approved')
                    ->where('id', '!=', $conversion->id)
                    ->count();

                if ($previousApprovedCount > 0) {
                    return null; // Not first conversion
                }
            }
        }

        // Calculate reward amount
        $snapshot = $conversion->payoutSnapshot;
        $affiliateCommission = $snapshot ? (float) $snapshot->affiliate_commission : 0.00;

        if ($rewardType === 'percentage') {
            $rewardAmount = round(($affiliateCommission * ($rewardValue / 100)), 2);
        } else {
            $rewardAmount = $rewardValue;
        }

        if ($rewardAmount <= 0) {
            return null;
        }

        // Prevent duplicate referral reward for same conversion
        $existing = ReferralEarning::where('conversion_id', $conversion->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($referrer, $referredUser, $ruleId, $ruleVersion, $conversion, $rewardType, $rewardAmount) {
            // Record referral earning
            $earning = ReferralEarning::create([
                'referrer_id' => $referrer->id,
                'referred_user_id' => $referredUser->id,
                'referral_rule_id' => $ruleId,
                'rule_version' => $ruleVersion,
                'conversion_id' => $conversion->id,
                'reward_type' => $rewardType,
                'reward_amount' => $rewardAmount,
                'status' => 'approved',
                'reference' => 'REF_' . strtoupper(substr($conversion->public_id, 0, 8)),
                'processed_at' => now(),
            ]);

            // Credit referrer's wallet
            $this->ledgerService->recordTransaction(
                user: $referrer,
                type: 'referral_earning',
                direction: 'credit',
                amount: $rewardAmount,
                conversion: $conversion,
                reference: 'REF_' . strtoupper(substr($conversion->public_id, 0, 8)),
                description: "Referral bonus from team member {$referredUser->name} for campaign conversion"
            );

            return $earning;
        });
    }

    /**
     * Handle referral reward reversal if conversion is reversed/rejected by admin.
     */
    public function reverseConversionReferralReward(Conversion $conversion): void
    {
        if (!Schema::hasTable('referral_earnings')) {
            return;
        }

        $earning = ReferralEarning::where('conversion_id', $conversion->id)
            ->where('status', 'approved')
            ->first();

        if (!$earning) {
            return;
        }

        DB::transaction(function () use ($earning, $conversion) {
            $earning->update(['status' => 'reversed']);

            $referrer = User::find($earning->referrer_id);
            if ($referrer) {
                $this->ledgerService->recordTransaction(
                    user: $referrer,
                    type: 'referral_reversal',
                    direction: 'debit',
                    amount: (float) $earning->reward_amount,
                    conversion: $conversion,
                    reference: 'REV_REF_' . strtoupper(substr($conversion->public_id, 0, 8)),
                    description: "Referral reward reversal due to conversion cancellation #{$conversion->id}"
                );
            }
        });
    }
}
