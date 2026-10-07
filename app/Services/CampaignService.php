<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAffiliate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CampaignService
{
    public function createCampaign(array $data, ?int $adminId = null): Campaign
    {
        return DB::transaction(function () use ($data, $adminId) {
            $data['created_by'] = $adminId;
            $data['updated_by'] = $adminId;
            $campaign = Campaign::create($data);

            AuditService::log(
                action: 'campaign_created',
                entityType: Campaign::class,
                entityId: $campaign->id,
                newValues: $campaign->toArray(),
                actorType: 'admin',
                actorId: $adminId
            );

            return $campaign;
        });
    }

    public function updateCampaign(Campaign $campaign, array $data, ?int $adminId = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $data, $adminId) {
            $oldValues = $campaign->toArray();
            $data['updated_by'] = $adminId;
            $campaign->update($data);

            AuditService::log(
                action: 'campaign_updated',
                entityType: Campaign::class,
                entityId: $campaign->id,
                oldValues: $oldValues,
                newValues: $campaign->toArray(),
                actorType: 'admin',
                actorId: $adminId
            );

            return $campaign;
        });
    }

    /**
     * Set Level 2 custom affiliate payout allocation.
     */
    public function setAffiliateAllocation(Campaign $campaign, User $user, float $affiliatePayout, string $status = 'allowed', ?int $adminId = null): CampaignAffiliate
    {
        if ($affiliatePayout < 0) {
            throw new InvalidArgumentException('Affiliate payout allocation cannot be negative.');
        }

        return DB::transaction(function () use ($campaign, $user, $affiliatePayout, $status, $adminId) {
            $allocation = CampaignAffiliate::updateOrCreate(
                ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                ['affiliate_payout' => $affiliatePayout, 'status' => $status]
            );

            AuditService::log(
                action: 'campaign_affiliate_allocation_updated',
                entityType: CampaignAffiliate::class,
                entityId: $allocation->id,
                newValues: [
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'affiliate_payout' => $affiliatePayout,
                    'status' => $status,
                ],
                actorType: 'admin',
                actorId: $adminId
            );

            return $allocation;
        });
    }

    /**
     * Resolve Level 2 affiliate payout for a user.
     */
    public function getAffiliatePayout(Campaign $campaign, User $user): float
    {
        return $campaign->getAffiliatePayoutForUser($user->id);
    }
}
