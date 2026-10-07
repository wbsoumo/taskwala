<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Services\TrackingService;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    protected TrackingService $trackingService;

    public function __construct(TrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    public function redirect(string $token, Request $request)
    {
        try {
            $result = $this->trackingService->processClick($token, $request);

            return redirect()->away($result['target_url']);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }
}
