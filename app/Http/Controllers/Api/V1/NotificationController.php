<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = UserNotification::where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'body' => $item->body,
                    'deeplink' => $item->deeplink,
                    'image_url' => $item->image_url,
                    'is_read' => (bool) $item->is_read,
                    'read_at' => $item->read_at?->toISOString(),
                    'created_at' => $item->created_at?->toISOString(),
                    'time_ago' => $item->created_at?->diffForHumans() ?? '',
                ];
            });

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $notification = UserNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'notification' => [
                'id' => $notification->id,
                'is_read' => true,
            ],
        ]);
    }

    public function markAsUnread(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $notification = UserNotification::where('user_id', $user->id)->findOrFail($id);
        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread.',
            'notification' => [
                'id' => $notification->id,
                'is_read' => false,
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
