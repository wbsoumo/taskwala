<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Services\AffiliateLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    protected AffiliateLinkService $linkService;

    public function __construct(AffiliateLinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    public function index(Request $request): JsonResponse
    {
        $links = AffiliateLink::with(['campaign.collaterals' => function ($q) {
            $q->where('is_active', true)->orderBy('sort_order');
        }])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(50);

        return response()->json($links);
    }

    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $query = AffiliateLink::with(['campaign.collaterals' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])->where('user_id', $user->id);

            if (is_numeric($id)) {
                $query->where(function ($sub) use ($id) {
                    $sub->where('id', (int) $id)->orWhere('public_id', (string) $id);
                });
            } else {
                $query->where(function ($sub) use ($id) {
                    $sub->where('public_id', (string) $id)
                        ->orWhere('secure_token', (string) $id);
                });
            }

            $link = $query->first();

            if (!$link) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tracking link not found.',
                ], 404);
            }

            $isCurrentlyActive = (strtolower($link->status) === 'active');
            $newStatus = $isCurrentlyActive ? 'paused' : 'active';

            try {
                $link->status = $newStatus;
                $link->save();
            } catch (\Throwable $saveErr) {
                // Fallback to 'disabled' if table enum hasn't been altered yet
                $newStatus = $isCurrentlyActive ? 'disabled' : 'active';
                $link->status = $newStatus;
                $link->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Tracking link successfully " . ($link->status === 'active' ? 'activated' : 'paused') . ".",
                'status' => $link->status,
                'is_active' => ($link->status === 'active'),
                'data' => $link,
                'link' => $link,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Link toggleStatus error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_public_id' => ['nullable', 'string'],
            'campaign_id' => ['nullable'],
            'customer_payout' => ['required', 'numeric', 'min:0'],
        ]);

        $campaignIdentifier = $request->input('campaign_public_id') ?? $request->input('campaign_id');
        if (!$campaignIdentifier) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign identifier (campaign_public_id or campaign_id) is required.'
            ], 422);
        }

        $campaign = Campaign::where('public_id', $campaignIdentifier)
            ->orWhere('id', $campaignIdentifier)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found or inactive.'
            ], 404);
        }

        $user = $request->user();

        try {
            $link = $this->linkService->generateLink(
                user: $user,
                campaign: $campaign,
                requestedCustomerPayout: (float) $request->customer_payout
            );

            return response()->json([
                'success' => true,
                'message' => 'Link generated successfully.',
                'public_url' => $link->public_url,
                'data' => array_merge($link->load('campaign')->toArray(), [
                    'public_url' => $link->public_url,
                    'campaign_name' => $campaign->name,
                ]),
                'link' => [
                    'id' => $link->id,
                    'public_id' => $link->public_id,
                    'campaign_name' => $campaign->name,
                    'secure_token' => $link->secure_token,
                    'customer_payout' => (float) $link->customer_payout,
                    'affiliate_commission' => (float) $link->affiliate_commission,
                    'public_url' => $link->public_url,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
