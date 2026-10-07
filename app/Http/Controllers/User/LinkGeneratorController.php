<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\AffiliateLinkService;
use App\Services\CampaignService;
use Illuminate\Http\Request;

class LinkGeneratorController extends Controller
{
    protected AffiliateLinkService $linkService;
    protected CampaignService $campaignService;

    public function __construct(AffiliateLinkService $linkService, CampaignService $campaignService)
    {
        $this->linkService = $linkService;
        $this->campaignService = $campaignService;
    }

    public function index(Request $request)
    {
        $user = auth('web')->user();

        $campaigns = Campaign::where('status', 'active')
            ->get()
            ->map(function ($c) use ($user) {
                $c->allocated_payout = $this->campaignService->getAffiliatePayout($c, $user);
                return $c;
            });

        $selectedCampaign = null;
        if ($request->filled('campaign_id')) {
            $selectedCampaign = $campaigns->firstWhere('id', $request->campaign_id);
        }

        return view('user.links.generator', compact('campaigns', 'selectedCampaign'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'customer_payout' => ['required', 'numeric', 'min:0'],
        ]);

        $user = auth('web')->user();
        $campaign = Campaign::where('id', $validated['campaign_id'])->where('status', 'active')->firstOrFail();

        try {
            $link = $this->linkService->generateLink(
                user: $user,
                campaign: $campaign,
                requestedCustomerPayout: (float) $validated['customer_payout']
            );

            return redirect()->route('user.links.index')->with('success', "Unique campaign link generated successfully! Public URL: {$link->public_url}");
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['customer_payout' => $e->getMessage()]);
        }
    }
}
