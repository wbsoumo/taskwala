<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * Get paginated active deals for mobile app.
     */
    public function index(Request $request)
    {
        $query = Deal::active();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'discount' => $query->orderBy('discount_percent', 'desc'),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'popular' => $query->orderBy('clicks_count', 'desc'),
            default => $query->orderBy('posted_at', 'desc'),
        };

        $perPage = min((int) $request->get('per_page', 20), 50);
        $deals = $query->paginate($perPage);

        $categories = Deal::active()->select('category')->distinct()->pluck('category');

        return response()->json([
            'status' => 'success',
            'data' => $deals->items(),
            'categories' => $categories,
            'pagination' => [
                'current_page' => $deals->currentPage(),
                'last_page' => $deals->lastPage(),
                'per_page' => $deals->perPage(),
                'total' => $deals->total(),
            ],
        ]);
    }

    /**
     * Get single deal details.
     */
    public function show(int $id)
    {
        $deal = Deal::active()->where('id', $id)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $deal,
        ]);
    }

    /**
     * Record a click on mobile app and return tracking redirect URL.
     */
    public function recordClick(int $id)
    {
        $deal = Deal::findOrFail($id);
        $deal->increment('clicks_count');

        return response()->json([
            'status' => 'success',
            'redirect_url' => $deal->effective_url,
            'clicks_count' => $deal->clicks_count,
        ]);
    }
}
