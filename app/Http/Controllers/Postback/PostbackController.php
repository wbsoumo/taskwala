<?php

namespace App\Http\Controllers\Postback;

use App\Http\Controllers\Controller;
use App\Services\PostbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostbackController extends Controller
{
    protected PostbackService $postbackService;

    public function __construct(PostbackService $postbackService)
    {
        $this->postbackService = $postbackService;
    }

    public function handle(string $provider_slug, Request $request): JsonResponse
    {
        $result = $this->postbackService->handlePostback($provider_slug, $request);

        return response()->json($result, $result['code'] ?? 200);
    }
}
