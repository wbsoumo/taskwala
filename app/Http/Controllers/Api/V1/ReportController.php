<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Conversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function clicks(Request $request): JsonResponse
    {
        $query = Click::with(['campaign', 'link', 'conversion'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('click_id', 'like', "%{$search}%")
                    ->orWhereHas('campaign', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'converted') {
                $query->has('conversion');
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($request->filled('date_range')) {
            $range = strtolower($request->date_range);
            if ($range === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($range === 'week') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($range === 'month') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $clicks = $query->latest('created_at')->paginate($perPage > 0 ? $perPage : 20);

        return response()->json($clicks);
    }

    public function conversions(Request $request): JsonResponse
    {
        $query = Conversion::with(['campaign', 'payoutSnapshot', 'customerPayout', 'click'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status') && strtolower($request->status) !== 'all') {
            $query->where('status', strtolower($request->status));
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                    ->orWhere('click_id', 'like', "%{$search}%")
                    ->orWhere('provider_conversion_id', 'like', "%{$search}%")
                    ->orWhereHas('campaign', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_range')) {
            $range = strtolower($request->date_range);
            if ($range === 'today') {
                $query->whereDate('conversion_time', today());
            } elseif ($range === 'week') {
                $query->where('conversion_time', '>=', now()->subDays(7));
            } elseif ($range === 'month') {
                $query->where('conversion_time', '>=', now()->subDays(30));
            }
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $conversions = $query->latest('conversion_time')->paginate($perPage > 0 ? $perPage : 20);

        return response()->json($conversions);
    }
}
