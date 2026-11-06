<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\AuditService;
use App\Services\Deals\DealAggregatorService;
use App\Services\Deals\EarnKaroService;
use Illuminate\Http\Request;

class DealController extends Controller
{
    protected EarnKaroService $earnkaroService;
    protected DealAggregatorService $aggregator;

    public function __construct(EarnKaroService $earnkaroService, DealAggregatorService $aggregator)
    {
        $this->earnkaroService = $earnkaroService;
        $this->aggregator = $aggregator;
    }

    /**
     * Display a listing of aggregated deals with filters and metrics.
     */
    public function index(Request $request)
    {
        $query = Deal::query();

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Source channel filter
        if ($request->filled('channel') && $request->channel !== 'all') {
            $query->where('source_channel', $request->channel);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'oldest' => $query->orderBy('posted_at', 'asc'),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'discount' => $query->orderBy('discount_percent', 'desc'),
            'clicks' => $query->orderBy('clicks_count', 'desc'),
            default => $query->orderBy('posted_at', 'desc'),
        };

        $deals = $query->paginate(20)->withQueryString();

        // Metric summaries
        $metrics = [
            'total' => Deal::count(),
            'active' => Deal::where('status', 'active')->count(),
            'expired' => Deal::where('status', 'expired')->count(),
            'converted' => Deal::where('conversion_status', 'converted')->count(),
            'total_clicks' => Deal::sum('clicks_count'),
        ];

        // Unique categories and channels for filter dropdowns
        $categories = Deal::select('category')->distinct()->pluck('category');
        $channels = Deal::select('source_channel')->distinct()->pluck('source_channel');

        return view('admin.deals.index', compact('deals', 'metrics', 'categories', 'channels'));
    }

    /**
     * Show the form for creating a new deal manually.
     */
    public function create()
    {
        return view('admin.deals.create');
    }

    /**
     * Store a newly created deal in database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'original_url' => 'required|url',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'commission_rate' => 'nullable|string|max:100',
            'category' => 'required|string|max:100',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'status' => 'required|in:active,expired,hidden',
        ]);

        // Attempt automatic EarnKaro link conversion
        $conversion = $this->earnkaroService->convertUrl($validated['original_url']);

        $deal = Deal::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'original_url' => $validated['original_url'],
            'affiliate_url' => $conversion['affiliate_url'] ?? null,
            'price' => $validated['price'] ?? null,
            'original_price' => $validated['original_price'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? null,
            'commission_rate' => $validated['commission_rate'] ?? null,
            'category' => $validated['category'],
            'image_url' => $validated['image_url'] ?? null,
            'source_channel' => 'Manual Admin',
            'status' => $validated['status'],
            'conversion_status' => $conversion['status'],
            'conversion_error' => $conversion['error'],
            'posted_at' => now(),
        ]);

        AuditService::log(
            action: 'deal_created',
            entityType: Deal::class,
            entityId: (string) $deal->id,
            newValues: $deal->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.deals.index')->with('success', 'Deal created successfully with affiliate link conversion.');
    }

    /**
     * Show the form for editing the specified deal.
     */
    public function edit(Deal $deal)
    {
        return view('admin.deals.edit', compact('deal'));
    }

    /**
     * Update the specified deal in database.
     */
    public function update(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'original_url' => 'required|url',
            'affiliate_url' => 'nullable|url',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'commission_rate' => 'nullable|string|max:100',
            'category' => 'required|string|max:100',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'status' => 'required|in:active,expired,hidden',
        ]);

        $old = $deal->toArray();

        // If original URL changed, re-hash and re-convert
        if ($deal->original_url !== $validated['original_url'] && empty($validated['affiliate_url'])) {
            $conversion = $this->earnkaroService->convertUrl($validated['original_url']);
            $validated['affiliate_url'] = $conversion['affiliate_url'] ?? null;
            $validated['conversion_status'] = $conversion['status'];
            $validated['conversion_error'] = $conversion['error'];
            $validated['url_hash'] = Deal::generateUrlHash($validated['original_url']);
        }

        $deal->update($validated);

        AuditService::log(
            action: 'deal_updated',
            entityType: Deal::class,
            entityId: (string) $deal->id,
            oldValues: $old,
            newValues: $deal->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        return redirect()->route('admin.deals.index')->with('success', 'Deal updated successfully.');
    }

    /**
     * Remove the specified deal from database.
     */
    public function destroy(Deal $deal)
    {
        AuditService::log(
            action: 'deal_deleted',
            entityType: Deal::class,
            entityId: (string) $deal->id,
            oldValues: $deal->toArray(),
            actorType: 'admin',
            actorId: auth('admin')->id()
        );

        $deal->delete();

        return redirect()->route('admin.deals.index')->with('success', 'Deal deleted successfully.');
    }

    /**
     * Toggle status of deal (e.g. active <-> expired).
     */
    public function toggleStatus(Deal $deal)
    {
        $newStatus = $deal->status === 'active' ? 'expired' : 'active';
        $deal->update(['status' => $newStatus]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'status' => $newStatus]);
        }

        return back()->with('success', "Deal marked as {$newStatus}.");
    }

    /**
     * Re-convert link via EarnKaro.
     */
    public function retryConversion(Deal $deal)
    {
        $conversion = $this->aggregator->retryDealConversion($deal);

        if ($conversion['success']) {
            return back()->with('success', 'Affiliate link successfully converted: ' . $deal->affiliate_url);
        }

        return back()->with('error', 'Link conversion failed: ' . ($conversion['error'] ?? 'Unknown error'));
    }
}
