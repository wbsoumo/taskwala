<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\CustomerPayout;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RedirectController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle public affiliate link hop (/go/{token}).
     */
    public function redirect(string $token, Request $request)
    {
        /** @var AffiliateLink $link */
        $link = AffiliateLink::with(['campaign', 'user'])
            ->where('secure_token', $token)
            ->first();

        if (!$link || $link->status !== 'active' || !$link->campaign->isActive() || !$link->user->isActive()) {
            abort(404, 'Campaign offer link is inactive or expired.');
        }

        // If customer payout is 0, redirect directly to advertiser URL
        if ((float) $link->customer_payout <= 0) {
            $result = $this->trackingService->processClick($token, $request);
            return redirect()->away($result['target_url']);
        }

        // Otherwise, render Public Offer Page
        $campaign = $link->campaign;
        $customerPayout = (float) $link->customer_payout;
        $theme = $campaign->theme ?? 'gradient_blue';

        return view('public.offer', compact('link', 'campaign', 'customerPayout', 'theme'));
    }

    /**
     * Handle public offer page form submission (UPI submission & task completion).
     */
    public function submitTask(string $token, Request $request)
    {
        /** @var AffiliateLink $link */
        $link = AffiliateLink::with(['campaign', 'user'])
            ->where('secure_token', $token)
            ->first();

        if (!$link || $link->status !== 'active' || !$link->campaign->isActive() || !$link->user->isActive()) {
            abort(404, 'Campaign offer link is inactive or expired.');
        }

        $validated = $request->validate([
            'upi_id' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/'
            ],
            'upi_holder_name' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
        ], [
            'upi_id.regex' => 'Please enter a valid UPI ID format (e.g. username@ybl, username@sbi).',
        ]);

        // Process visitor click & generate click_id + target URL
        $result = $this->trackingService->processClick($token, $request);

        // Store customer payout details linked to click
        CustomerPayout::create([
            'link_id' => $link->id,
            'click_id' => $result['click']->click_id,
            'customer_name' => $validated['upi_holder_name'] ?? null,
            'upi_id' => strtolower(trim($validated['upi_id'])),
            'upi_holder_name' => $validated['upi_holder_name'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'payout_amount' => $link->customer_payout,
            'status' => 'pending',
        ]);

        return redirect()->away($result['target_url']);
    }
}
