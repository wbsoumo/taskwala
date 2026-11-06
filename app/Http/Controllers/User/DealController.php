<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * Display a public/affiliate listing of active deals.
     */
    public function index(Request $request)
    {
        $query = Deal::active();

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'discount' => $query->orderBy('discount_percent', 'desc'),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('clicks_count', 'desc'),
            default => $query->orderBy('posted_at', 'desc'),
        };

        $deals = $query->paginate(24)->withQueryString();

        // Distinct categories for filter buttons
        $categories = Deal::active()->select('category')->distinct()->pluck('category');

        $featuredDeals = Deal::active()
            ->whereNotNull('discount_percent')
            ->where('discount_percent', '>=', 40)
            ->orderBy('discount_percent', 'desc')
            ->take(4)
            ->get();

        return view('user.deals.index', compact('deals', 'categories', 'featuredDeals'));
    }

    /**
     * Display detailed view of a specific deal.
     */
    public function show(string $publicId)
    {
        $deal = Deal::where('public_id', $publicId)->firstOrFail();

        // Related deals in same category
        $relatedDeals = Deal::active()
            ->where('id', '!=', $deal->id)
            ->where('category', $deal->category)
            ->orderBy('posted_at', 'desc')
            ->take(4)
            ->get();

        return view('user.deals.show', compact('deal', 'relatedDeals'));
    }

    /**
     * Outbound click redirect for deals: increments click counter and forwards to converted URL.
     */
    public function redirect(string $publicId)
    {
        $deal = Deal::where('public_id', $publicId)->firstOrFail();
        $deal->increment('clicks_count');

        $destination = $deal->effective_url;

        return redirect()->away($destination);
    }
}
