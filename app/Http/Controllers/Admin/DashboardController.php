<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\PayoutSnapshot;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalCampaigns = Campaign::count();
        $activeCampaigns = Campaign::where('status', 'active')->count();
        $totalAffiliates = User::count();
        $activeAffiliates = User::where('status', 'active')->count();

        $totalClicks = Click::count();
        $totalConversions = Conversion::count();
        $approvedConversions = Conversion::where('status', 'approved')->count();
        $pendingConversions = Conversion::where('status', 'pending')->count();
        $rejectedConversions = Conversion::whereIn('status', ['rejected', 'cancelled', 'reversed'])->count();

        // Financial Metrics from immutable snapshots
        $totalAdvertiserRevenue = (float) PayoutSnapshot::sum('advertiser_payout');
        $totalAffiliateAllocation = (float) PayoutSnapshot::sum('affiliate_allocated_payout');
        $totalCustomerPayout = (float) PayoutSnapshot::sum('customer_payout');
        $totalAffiliateCommission = (float) PayoutSnapshot::sum('affiliate_commission');
        $totalPlatformMargin = (float) PayoutSnapshot::sum('platform_margin');

        // Recent Activity
        $recentConversions = Conversion::with(['campaign', 'user', 'payoutSnapshot'])
            ->latest()
            ->limit(10)
            ->get();

        $recentClicks = Click::with(['campaign', 'user'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalCampaigns',
            'activeCampaigns',
            'totalAffiliates',
            'activeAffiliates',
            'totalClicks',
            'totalConversions',
            'approvedConversions',
            'pendingConversions',
            'rejectedConversions',
            'totalAdvertiserRevenue',
            'totalAffiliateAllocation',
            'totalCustomerPayout',
            'totalAffiliateCommission',
            'totalPlatformMargin',
            'recentConversions',
            'recentClicks'
        ));
    }
}
