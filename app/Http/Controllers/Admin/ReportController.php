<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\PayoutSnapshot;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function performance(Request $request)
    {
        $query = Click::with(['campaign', 'user', 'link', 'customerPayout', 'conversion.payoutSnapshot', 'conversion.provider', 'conversion.customerPayout']);

        // Search Filter (Search across click_id, conversion_id, UPI ID, affiliate name, campaign name)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('click_id', 'like', "%{$search}%")
                  ->orWhereHas('customerPayout', fn($cp) => $cp->where('upi_id', 'like', "%{$search}%"))
                  ->orWhereHas('campaign', fn($c) => $c->where('name', 'like', "%{$search}%")->orWhere('advertiser_name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('conversion', function ($conv) use ($search) {
                      $conv->where('provider_conversion_id', 'like', "%{$search}%")
                           ->orWhereHas('customerPayout', fn($cp) => $cp->where('upi_id', 'like', "%{$search}%"));
                  });
            });
        }

        // Campaign Filter
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Affiliate / User Filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Conversion Status Filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'clicked') {
                $query->doesntHave('conversion');
            } else {
                $query->whereHas('conversion', fn($c) => $c->where('status', $status));
            }
        }

        // Date Range Filters
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['created_at', 'click_id', 'allocated_affiliate_payout', 'customer_payout', 'affiliate_commission'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('created_at');
        }

        $records = $query->paginate(20)->withQueryString();

        $campaigns = Campaign::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.reports.performance', compact('records', 'campaigns', 'users'));
    }

    public function exportPerformanceCsv(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="performance_report_' . date('Y-m-d_H-i') . '.csv"',
        ];

        $query = Click::with(['campaign', 'user', 'link', 'customerPayout', 'conversion.payoutSnapshot', 'conversion.provider', 'conversion.customerPayout']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('click_id', 'like', "%{$search}%")
                  ->orWhereHas('customerPayout', fn($cp) => $cp->where('upi_id', 'like', "%{$search}%"))
                  ->orWhereHas('campaign', fn($c) => $c->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('conversion', fn($conv) => $conv->whereHas('customerPayout', fn($cp) => $cp->where('upi_id', 'like', "%{$search}%")));
            });
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->latest('created_at')->get();

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Click ID',
                'Date & Time',
                'Campaign Name',
                'Advertiser Name',
                'Affiliate Name',
                'Affiliate Email',
                'Conversion Status',
                'Provider Conversion ID',
                'Customer UPI ID',
                'Advertiser Gross (L1)',
                'Affiliate Allocation (L2)',
                'Customer Payout (L3)',
                'Affiliate Commission',
                'Platform Margin',
            ]);

            foreach ($records as $row) {
                $conv = $row->conversion;
                $snap = $conv?->payoutSnapshot;
                $upi = $row->customerPayout?->upi_id ?? $conv?->customerPayout?->upi_id ?? 'N/A';

                fputcsv($file, [
                    $row->click_id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->campaign->name ?? 'N/A',
                    $row->campaign->advertiser_name ?? 'N/A',
                    $row->user->name ?? 'N/A',
                    $row->user->email ?? 'N/A',
                    $conv ? ucfirst($conv->status) : 'Clicked (Unconverted)',
                    $conv->provider_conversion_id ?? 'N/A',
                    $upi,
                    $snap ? $snap->advertiser_payout : '0.00',
                    $snap ? $snap->affiliate_allocated_payout : '0.00',
                    $snap ? $snap->customer_payout : '0.00',
                    $snap ? $snap->affiliate_commission : '0.00',
                    $snap ? $snap->platform_margin : '0.00',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

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
