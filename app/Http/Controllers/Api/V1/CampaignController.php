<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $campaigns = Campaign::where('status', 'active')
            ->get()
            ->map(function ($c) use ($user) {
                return [
                    'id' => $c->id,
                    'public_id' => $c->public_id,
                    'name' => $c->name,
                    'category' => $c->category,
                    'short_description' => $c->short_description,
                    'advertiser_payout' => (float) $c->advertiser_payout,
                    'allocated_affiliate_payout' => $this->campaignService->getAffiliatePayout($c, $user),
                    'currency' => $c->currency,
                    'terms' => $c->terms,
                    'check_duplicate_ip' => (bool) $c->check_duplicate_ip,
                ];
            });

        return response()->json(['campaigns' => $campaigns]);
    }

    public function checkIp(int|string $id, Request $request): JsonResponse
    {
        $campaign = Campaign::where('id', $id)->orWhere('public_id', $id)->first();
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found'], 404);
        }

        $trackingService = app(\App\Services\TrackingService::class);
        $clientIp = $request->ip();
        $isDuplicate = $trackingService->hasIpEngagedWithCampaign($campaign, $clientIp);

        return response()->json([
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'client_ip' => $clientIp,
            'check_enabled' => (bool) $campaign->check_duplicate_ip,
            'is_duplicate' => $isDuplicate,
            'message' => $isDuplicate
                ? "This IP address ({$clientIp}) has already participated in this task. Please switch to Mobile Data or another network to ensure successful tracking."
                : "IP is clean and ready for conversion tracking.",
        ]);
    }

    public function earnLink(int|string $id, Request $request): JsonResponse
    {
        $campaign = Campaign::where('id', $id)->orWhere('public_id', $id)->where('status', 'active')->first();
        if (!$campaign) {
            return response()->json(['success' => false, 'message' => 'Campaign not found or inactive'], 404);
        }

        $user = $request->user();
        $linkService = app(\App\Services\AffiliateLinkService::class);

        // Generate or get direct earn link (0 customer payout so user earns 100% affiliate payout)
        $link = $linkService->generateLink(
            user: $user,
            campaign: $campaign,
            requestedCustomerPayout: 0
        );

        $trackingService = app(\App\Services\TrackingService::class);
        $clientIp = $request->ip();
        $isDuplicate = $trackingService->hasIpEngagedWithCampaign($campaign, $clientIp);

        return response()->json([
            'success' => true,
            'link' => $link,
            'public_url' => $link->public_url,
            'client_ip' => $clientIp,
            'check_enabled' => (bool) $campaign->check_duplicate_ip,
            'is_duplicate' => $isDuplicate,
            'payout_amount' => (float) $link->allocated_affiliate_payout,
            'message' => $isDuplicate
                ? "Duplicate IP detected. Please change your IP to ensure conversion tracking."
                : "Ready to proceed.",
        ]);
    }
}
