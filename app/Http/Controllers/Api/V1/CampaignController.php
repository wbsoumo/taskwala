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
                    'public_id' => $c->public_id,
                    'name' => $c->name,
                    'category' => $c->category,
                    'short_description' => $c->short_description,
                    'advertiser_payout' => (float) $c->advertiser_payout,
                    'allocated_affiliate_payout' => $this->campaignService->getAffiliatePayout($c, $user),
                    'currency' => $c->currency,
                    'terms' => $c->terms,
                ];
            });

        return response()->json(['campaigns' => $campaigns]);
    }
}
