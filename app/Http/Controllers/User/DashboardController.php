<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\PayoutSnapshot;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('web')->user();
        $user->load('wallet');

        $totalLinks = AffiliateLink::where('user_id', $user->id)->count();
        $totalClicks = Click::where('user_id', $user->id)->count();

        $userConversions = Conversion::where('user_id', $user->id);
        $totalConversions = (clone $userConversions)->count();
        $approvedConversions = (clone $userConversions)->where('status', 'approved')->count();
        $pendingConversions = (clone $userConversions)->where('status', 'pending')->count();

        // Calculate earnings from immutable conversion snapshots
        $approvedConversionIds = (clone $userConversions)->where('status', 'approved')->pluck('id');
        $snapshots = PayoutSnapshot::whereIn('conversion_id', $approvedConversionIds)->get();

        $totalAffiliateCommission = (float) $snapshots->sum('affiliate_commission');
        $totalCustomerRewardsGenerated = (float) $snapshots->sum('customer_payout');

        $availableBalance = (float) ($user->wallet->balance ?? 0.00);
        $pendingBalance = (float) ($user->wallet->pending_balance ?? 0.00);

        $recentConversions = Conversion::with(['campaign', 'payoutSnapshot'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentLinks = AffiliateLink::with('campaign')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('user.dashboard', compact(
            'user',
            'totalLinks',
            'totalClicks',
            'totalConversions',
            'approvedConversions',
            'pendingConversions',
            'totalAffiliateCommission',
            'totalCustomerRewardsGenerated',
            'availableBalance',
            'pendingBalance',
            'recentConversions',
            'recentLinks'
        ));
    }
}
