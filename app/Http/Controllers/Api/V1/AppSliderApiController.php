<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSlider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppSliderApiController extends Controller
{
    /**
     * Return active promotional sliders and video banners for mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        $sliders = AppSlider::active()->get()->map(function ($slider) {
            return [
                'id' => $slider->id,
                'title' => $slider->title,
                'subtitle' => $slider->subtitle,
                'badge_text' => $slider->badge_text,
                'media_type' => $slider->media_type, // 'image' or 'video'
                'media_url' => $slider->media_full_url,
                'thumbnail_url' => $slider->thumbnail_full_url ?: ($slider->media_type === 'image' ? $slider->media_full_url : null),
                'action_type' => $slider->action_type, // 'none', 'campaign', 'deal', 'app_route', 'external_url'
                'action_value' => $slider->action_value,
                'auto_play_video' => $slider->auto_play_video,
                'loop_video' => $slider->loop_video,
                'order' => $slider->order,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $sliders->count(),
            'sliders' => $sliders,
        ]);
    }
}
