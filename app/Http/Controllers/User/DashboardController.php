<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\PayoutSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
        $rejectedConversions = (clone $userConversions)->where('status', 'rejected')->count();

        // Calculate earnings from immutable conversion snapshots
        $approvedConversionIds = (clone $userConversions)->where('status', 'approved')->pluck('id');
        $approvedSnapshots = PayoutSnapshot::whereIn('conversion_id', $approvedConversionIds)->get();

        $pendingConversionIds = (clone $userConversions)->where('status', 'pending')->pluck('id');
        $pendingSnapshots = PayoutSnapshot::whereIn('conversion_id', $pendingConversionIds)->get();

        $approvedEarnings = (float) $approvedSnapshots->sum('affiliate_commission');
        $pendingEarnings = (float) $pendingSnapshots->sum('affiliate_commission');
        $totalCustomerRewardsGenerated = (float) $approvedSnapshots->sum('customer_payout');

        $availableBalance = (float) ($user->wallet->balance ?? 0.00);
        $pendingBalance = (float) ($user->wallet->pending_balance ?? 0.00);
        $paidEarnings = max(0, ($approvedEarnings - $availableBalance));

        // Chart Data (Last 7 Days)
        $range = $request->get('range', '7_days');
        $days = $range === '30_days' ? 30 : 7;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $chartLabels = [];
        $chartClicks = [];
        $chartConversions = [];
        $chartEarnings = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dateFormatted = Carbon::now()->subDays($i)->format('M d');

            $dayClicks = Click::where('user_id', $user->id)->whereDate('created_at', $date)->count();
            $dayConversions = Conversion::where('user_id', $user->id)->whereDate('conversion_time', $date)->count();
            
            $dayConvIds = Conversion::where('user_id', $user->id)->where('status', 'approved')->whereDate('conversion_time', $date)->pluck('id');
            $dayEarning = (float) PayoutSnapshot::whereIn('conversion_id', $dayConvIds)->sum('affiliate_commission');

            $chartLabels[] = $dateFormatted;
            $chartClicks[] = $dayClicks;
            $chartConversions[] = $dayConversions;
            $chartEarnings[] = $dayEarning;
        }

        // Recent Activity Feed
        $recentConversions = Conversion::with(['campaign', 'payoutSnapshot'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $recentClicks = Click::with('campaign')
            ->where('user_id', $user->id)
            ->latest('created_at')
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
            'rejectedConversions',
            'approvedEarnings',
            'pendingEarnings',
            'paidEarnings',
            'totalCustomerRewardsGenerated',
            'availableBalance',
            'pendingBalance',
            'recentConversions',
            'recentClicks',
            'recentLinks',
            'chartLabels',
            'chartClicks',
            'chartConversions',
            'chartEarnings',
            'range'
        ));
    }
}
