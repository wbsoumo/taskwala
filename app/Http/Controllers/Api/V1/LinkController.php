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
        $links = AffiliateLink::with('campaign')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($links);
    }

    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_public_id' => ['required', 'exists:campaigns,public_id'],
            'customer_payout' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $campaign = Campaign::where('public_id', $request->campaign_public_id)->firstOrFail();

        try {
            $link = $this->linkService->generateLink(
                user: $user,
                campaign: $campaign,
                requestedCustomerPayout: (float) $request->customer_payout
            );

            return response()->json([
                'message' => 'Link generated successfully.',
                'public_url' => $link->public_url,
                'link' => [
                    'public_id' => $link->public_id,
                    'secure_token' => $link->secure_token,
                    'customer_payout' => (float) $link->customer_payout,
                    'affiliate_commission' => (float) $link->affiliate_commission,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
