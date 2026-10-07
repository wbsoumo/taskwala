<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignAllocationController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'affiliate_payout' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['allowed', 'blocked'])],
        ]);

        $user = User::findOrFail($validated['user_id']);

        $this->campaignService->setAffiliateAllocation(
            campaign: $campaign,
            user: $user,
            affiliatePayout: (float) $validated['affiliate_payout'],
            status: $validated['status'],
            adminId: auth('admin')->id()
        );

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', "Payout allocation updated for affiliate {$user->name}.");
    }
}
