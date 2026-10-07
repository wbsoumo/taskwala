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
        $query = Click::with(['campaign', 'link'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $clicks = $query->latest('created_at')->paginate(20);

        return response()->json($clicks);
    }

    public function conversions(Request $request): JsonResponse
    {
        $query = Conversion::with(['campaign', 'payoutSnapshot', 'customerPayout', 'click'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $conversions = $query->latest('conversion_time')->paginate(20);

        return response()->json($conversions);
    }
}
