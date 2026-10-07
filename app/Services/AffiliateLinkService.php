<?php

namespace App\Services;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AffiliateLinkService
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Generate secure affiliate link with immutable Level 3 payout split configuration.
     *
     * @param User $user Authenticated affiliate
     * @param Campaign $campaign Campaign target
     * @param float $requestedCustomerPayout Customer payout requested by affiliate
     */
    public function generateLink(User $user, Campaign $campaign, float $requestedCustomerPayout): AffiliateLink
    {
        // 1. Verify User status
        if (!$user->isActive()) {
            throw new InvalidArgumentException('User account is suspended or blocked.');
        }

        // 2. Verify Campaign status & validity
        if (!$campaign->isActive()) {
            throw new InvalidArgumentException('Campaign is not currently active.');
        }

        // 3. Fetch Level 2 payout from database source of truth
        $allocatedAffiliatePayout = $this->campaignService->getAffiliatePayout($campaign, $user);

        // 4. Validate Level 3 customer payout
        $minCustomerPayout = (float) config('payout.min_customer_payout', 0.00);

        if ($requestedCustomerPayout < $minCustomerPayout) {
            throw new InvalidArgumentException("Customer payout cannot be less than {$minCustomerPayout}.");
        }

        if ($requestedCustomerPayout > $allocatedAffiliatePayout) {
            throw new InvalidArgumentException("Customer payout ({$requestedCustomerPayout}) cannot exceed your allocated payout ({$allocatedAffiliatePayout}).");
        }

        // 5. Calculate server-side commission (NEVER TRUST FRONTEND VALUE)
        $affiliateCommission = $allocatedAffiliatePayout - $requestedCustomerPayout;

        // 6. Generate cryptographically secure token with collision handling
        $secureToken = $this->generateUniqueToken();

        return DB::transaction(function () use ($user, $campaign, $allocatedAffiliatePayout, $requestedCustomerPayout, $affiliateCommission, $secureToken) {
            $link = AffiliateLink::create([
                'secure_token' => $secureToken,
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'allocated_affiliate_payout' => $allocatedAffiliatePayout,
                'customer_payout' => $requestedCustomerPayout,
                'affiliate_commission' => $affiliateCommission,
                'status' => 'active',
                'click_count' => 0,
                'conversion_count' => 0,
            ]);

            AuditService::log(
                action: 'affiliate_link_generated',
                entityType: AffiliateLink::class,
                entityId: $link->id,
                newValues: [
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'allocated_affiliate_payout' => $allocatedAffiliatePayout,
                    'customer_payout' => $requestedCustomerPayout,
                    'affiliate_commission' => $affiliateCommission,
                    'secure_token' => $secureToken,
                ],
                actorType: 'user',
                actorId: $user->id
            );

            return $link;
        });
    }

    /**
     * Generate 40-character secure token.
     */
    protected function generateUniqueToken(): string
    {
        do {
            $token = Str::random(config('tracking.token_length', 40));
        } while (AffiliateLink::where('secure_token', $token)->exists());

        return $token;
    }
}
