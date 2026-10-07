<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PayoutSnapshot;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function campaigns(Request $request)
    {
        $campaigns = Campaign::withCount(['clicks', 'conversions'])
            ->get()
            ->map(function ($campaign) {
                $conversions = $campaign->conversions()->pluck('id');
                $snapshots = PayoutSnapshot::whereIn('conversion_id', $conversions)->get();

                $campaign->total_advertiser_revenue = $snapshots->sum('advertiser_payout');
                $campaign->total_affiliate_allocation = $snapshots->sum('affiliate_allocated_payout');
                $campaign->total_customer_payout = $snapshots->sum('customer_payout');
                $campaign->total_affiliate_commission = $snapshots->sum('affiliate_commission');
                $campaign->total_platform_margin = $snapshots->sum('platform_margin');
                $campaign->conversion_rate = $campaign->clicks_count > 0 ? round(($campaign->conversions_count / $campaign->clicks_count) * 100, 2) : 0;

                return $campaign;
            });

        return view('admin.reports.campaigns', compact('campaigns'));
    }

    public function exportCampaignsCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_report_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Campaign ID',
                'Campaign Name',
                'Advertiser Name',
                'Clicks',
                'Conversions',
                'Conversion Rate (%)',
                'Advertiser Revenue',
                'Affiliate Allocation',
                'Customer Payout',
                'Affiliate Commission',
                'Platform Margin',
            ]);

            $campaigns = Campaign::withCount(['clicks', 'conversions'])->get();
            foreach ($campaigns as $campaign) {
                $conversions = $campaign->conversions()->pluck('id');
                $snapshots = PayoutSnapshot::whereIn('conversion_id', $conversions)->get();

                $rev = $snapshots->sum('advertiser_payout');
                $alloc = $snapshots->sum('affiliate_allocated_payout');
                $cust = $snapshots->sum('customer_payout');
                $comm = $snapshots->sum('affiliate_commission');
                $margin = $snapshots->sum('platform_margin');
                $cvr = $campaign->clicks_count > 0 ? round(($campaign->conversions_count / $campaign->clicks_count) * 100, 2) : 0;

                fputcsv($file, [
                    $campaign->id,
                    $campaign->name,
                    $campaign->advertiser_name,
                    $campaign->clicks_count,
                    $campaign->conversions_count,
                    $cvr,
                    $rev,
                    $alloc,
                    $cust,
                    $comm,
                    $margin,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function affiliates(Request $request)
    {
        $affiliates = User::withCount(['clicks', 'conversions'])
            ->with('wallet')
            ->get();

        return view('admin.reports.affiliates', compact('affiliates'));
    }

    public function financial(Request $request)
    {
        $totalRevenue = (float) PayoutSnapshot::sum('advertiser_payout');
        $totalAffiliateAllocation = (float) PayoutSnapshot::sum('affiliate_allocated_payout');
        $totalCustomerPayout = (float) PayoutSnapshot::sum('customer_payout');
        $totalAffiliateCommission = (float) PayoutSnapshot::sum('affiliate_commission');
        $totalMargin = (float) PayoutSnapshot::sum('platform_margin');

        return view('admin.reports.financial', compact(
            'totalRevenue',
            'totalAffiliateAllocation',
            'totalCustomerPayout',
            'totalAffiliateCommission',
            'totalMargin'
        ));
    }
}
