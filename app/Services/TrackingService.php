<?php

namespace App\Services;

use App\Models\AffiliateLink;
use App\Models\Click;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TrackingService
{
    /**
     * Resolve link by token, log visitor click, and return destination landing URL.
     */
    public function processClick(string $token, Request $request): array
    {
        /** @var AffiliateLink $link */
        $link = AffiliateLink::with(['campaign', 'user'])
            ->where('secure_token', $token)
            ->first();

        if (!$link) {
            throw new InvalidArgumentException('Invalid or expired tracking link.');
        }

        if ($link->status !== 'active') {
            throw new InvalidArgumentException('Tracking link is disabled.');
        }

        if (!$link->campaign->isActive()) {
            throw new InvalidArgumentException('Associated campaign is currently inactive.');
        }

        if (!$link->user->isActive()) {
            throw new InvalidArgumentException('Associated affiliate account is inactive.');
        }

        // Generate Server-Side Unique Click ID
        $prefix = config('tracking.click_id_prefix', 'CLK_');
        $clickId = $prefix . Str::ulid();

        // Detect basic device info safely from User Agent
        $userAgent = $request->userAgent() ?? '';
        $deviceType = $this->detectDeviceType($userAgent);
        $os = $this->detectOs($userAgent);
        $browser = $this->detectBrowser($userAgent);

        $click = DB::transaction(function () use ($link, $clickId, $request, $userAgent, $deviceType, $os, $browser) {
            // Increment link click counter
            $link->increment('click_count');

            return Click::create([
                'click_id' => $clickId,
                'link_id' => $link->id,
                'campaign_id' => $link->campaign_id,
                'user_id' => $link->user_id,
                'allocated_affiliate_payout' => $link->allocated_affiliate_payout,
                'customer_payout' => $link->customer_payout,
                'affiliate_commission' => $link->affiliate_commission,
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'user_agent' => $userAgent,
                'referrer' => $request->header('referer'),
                'device_type' => $deviceType,
                'os' => $os,
                'browser' => $browser,
                'status' => 'tracked',
                'created_at' => now(),
            ]);
        });

        // Build target landing URL by injecting macros
        $landingUrl = $link->campaign->landing_url;
        $targetUrl = str_replace(
            ['{click_id}', '{sub_id}'],
            [$clickId, $link->secure_token],
            $landingUrl
        );

        return [
            'click' => $click,
            'link' => $link,
            'target_url' => $targetUrl,
        ];
    }

    protected function detectDeviceType(string $ua): string
    {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/(android|bb\d+|meego).+mobile|mobile|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    protected function detectOs(string $ua): string
    {
        if (preg_match('/windows nt/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'macOS';
        if (preg_match('/android/i', $ua)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $ua)) return 'iOS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        return 'Unknown';
    }

    protected function detectBrowser(string $ua): string
    {
        if (preg_match('/edg/i', $ua)) return 'Edge';
        if (preg_match('/chrome/i', $ua)) return 'Chrome';
        if (preg_match('/firefox/i', $ua)) return 'Firefox';
        if (preg_match('/safari/i', $ua)) return 'Safari';
        return 'Unknown';
    }
}
