<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function clicks(Request $request)
    {
        $user = auth('web')->user();

        $query = Click::with(['campaign', 'link', 'conversion'])
            ->where('user_id', $user->id);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('link_id')) {
            $query->where('affiliate_link_id', $request->link_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $clicks = $query->latest('created_at')->paginate(20)->withQueryString();
        $campaigns = Campaign::where('status', 'active')->select('id', 'name')->get();
        $links = AffiliateLink::where('user_id', $user->id)->select('id', 'public_id', 'campaign_id')->get();

        return view('user.reports.clicks', compact('clicks', 'campaigns', 'links'));
    }

    public function conversions(Request $request)
    {
        $user = auth('web')->user();

        $query = Conversion::with(['campaign', 'payoutSnapshot', 'customerPayout', 'click'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('conversion_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('conversion_time', '<=', $request->end_date);
        }

        $conversions = $query->latest('conversion_time')->paginate(20)->withQueryString();
        $campaigns = Campaign::where('status', 'active')->select('id', 'name')->get();

        return view('user.reports.conversions', compact('conversions', 'campaigns'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = auth('web')->user();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="conversions_report_' . date('Y-m-d_H-i') . '.csv"',
        ];

        $query = Conversion::with(['campaign', 'payoutSnapshot', 'customerPayout', 'click'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('conversion_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('conversion_time', '<=', $request->end_date);
        }

        $records = $query->latest('conversion_time')->get();

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Date & Time',
                'Campaign Name',
                'Conversion ID',
                'Status',
                'Customer Payout',
                'Affiliate Commission',
                'Payout Status',
                'UPI ID',
            ]);

            foreach ($records as $row) {
                $snap = $row->payoutSnapshot;
                $upi = $row->customerPayout?->upi_id ?? 'N/A';
                $payoutStatus = $row->customerPayout ? ucfirst($row->customerPayout->status) : ($row->status === 'approved' ? 'Paid' : 'Pending');

                fputcsv($file, [
                    $row->conversion_time ? $row->conversion_time->format('Y-m-d H:i:s') : $row->created_at->format('Y-m-d H:i:s'),
                    $row->campaign->name ?? 'N/A',
                    $row->public_id,
                    ucfirst($row->status),
                    $snap ? $snap->customer_payout : '0.00',
                    $snap ? $snap->affiliate_commission : '0.00',
                    $payoutStatus,
                    $upi,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
