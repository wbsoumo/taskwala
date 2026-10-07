<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Click;
use App\Models\Conversion;
use App\Services\ConversionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrackingController extends Controller
{
    protected ConversionService $conversionService;

    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function links(Request $request)
    {
        $links = AffiliateLink::with(['campaign', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.tracking.links', compact('links'));
    }

    public function clicks(Request $request)
    {
        $clicks = Click::with(['campaign', 'user', 'link'])
            ->latest('created_at')
            ->paginate(25);

        return view('admin.tracking.clicks', compact('clicks'));
    }

    public function conversions(Request $request)
    {
        $query = Conversion::with(['campaign', 'user', 'payoutSnapshot', 'provider']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('click_id', 'like', "%{$search}%")
                  ->orWhere('provider_conversion_id', 'like', "%{$search}%");
            });
        }

        $conversions = $query->latest()->paginate(20);

        return view('admin.tracking.conversions', compact('conversions'));
    }

    public function conversionDetail(Conversion $conversion)
    {
        $conversion->load([
            'campaign',
            'user',
            'link',
            'click',
            'provider',
            'payoutSnapshot',
            'customerPayout',
            'statusHistories',
            'walletTransactions',
        ]);

        return view('admin.tracking.conversion_detail', compact('conversion'));
    }

    public function updateConversionStatus(Request $request, Conversion $conversion)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'reversed', 'cancelled', 'paid'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->conversionService->updateStatus(
            conversion: $conversion,
            newStatus: $validated['status'],
            reason: $validated['reason'] ?? '',
            adminId: auth('admin')->id()
        );

        return redirect()->route('admin.tracking.conversions.detail', $conversion)->with('success', 'Conversion status updated successfully.');
    }
}
