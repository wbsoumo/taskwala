<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index()
    {
        $user = auth('web')->user();

        $campaigns = Campaign::where('status', 'active')
            ->get()
            ->map(function ($campaign) use ($user) {
                $campaign->allocated_affiliate_payout = $this->campaignService->getAffiliatePayout($campaign, $user);
                return $campaign;
            });

        return view('user.campaigns.index', compact('campaigns'));
    }

    public function show(string $publicId)
    {
        $user = auth('web')->user();
        $campaign = Campaign::where('public_id', $publicId)->firstOrFail();

        if (!$campaign->isActive()) {
            abort(404, 'Campaign not found or inactive.');
        }

        $allocatedPayout = $this->campaignService->getAffiliatePayout($campaign, $user);

        return view('user.campaigns.show', compact('campaign', 'allocatedPayout'));
    }
}
