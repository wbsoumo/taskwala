<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class NotificationDispatcherService
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /**
     * Send an event-driven notification to a specific user (Push + In-app).
     */
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        ?string $deeplink = null,
        ?string $imageUrl = null
    ): array {
        $fcmStatus = 'no_token';
        $fcmError = null;

        if (!empty($user->fcm_token)) {
            $result = $this->firebase->sendNotification(
                fcmToken: $user->fcm_token,
                title: $title,
                body: $body,
                deeplink: $deeplink,
                imageUrl: $imageUrl
            );

            if (!empty($result['success'])) {
                $fcmStatus = 'sent';
            } else {
                $fcmStatus = 'failed';
                $fcmError = $result['error'] ?? 'Delivery failed';
                Log::warning("Event notification FCM failed for User #{$user->id}: {$fcmError}");
            }
        }

        // Save in-app notification record so it appears in user's Notification Center
        $userNotification = UserNotification::create([
            'app_notification_id' => null, // Event-driven personal notification
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'deeplink' => $deeplink ?: null,
            'image_url' => $imageUrl ?: null,
            'is_read' => false,
            'read_at' => null,
            'fcm_token' => $user->fcm_token,
            'fcm_status' => $fcmStatus,
            'fcm_error' => $fcmError,
        ]);

        return [
            'success' => true,
            'fcm_status' => $fcmStatus,
            'notification_id' => $userNotification->id,
        ];
    }

    /**
     * Broadcast an announcement to all active users (Push + In-app).
     */
    public function broadcast(
        string $title,
        string $body,
        ?string $deeplink = null,
        ?string $imageUrl = null,
        ?int $adminId = null
    ): AppNotification {
        $users = User::where('status', 'active')->get();

        $appNotification = AppNotification::create([
            'title' => $title,
            'body' => $body,
            'deeplink' => $deeplink ?: null,
            'image_url' => $imageUrl ?: null,
            'target_type' => 'all',
            'total_targeted' => $users->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by_admin_id' => $adminId,
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $fcmStatus = 'no_token';
            $fcmError = null;

            if (!empty($user->fcm_token)) {
                $result = $this->firebase->sendNotification(
                    fcmToken: $user->fcm_token,
                    title: $title,
                    body: $body,
                    deeplink: $deeplink,
                    imageUrl: $imageUrl
                );

                if (!empty($result['success'])) {
                    $fcmStatus = 'sent';
                    $sentCount++;
                } else {
                    $fcmStatus = 'failed';
                    $fcmError = $result['error'] ?? 'Delivery failed';
                    $failedCount++;
                }
            }

            UserNotification::create([
                'app_notification_id' => $appNotification->id,
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'deeplink' => $deeplink ?: null,
                'image_url' => $imageUrl ?: null,
                'is_read' => false,
                'read_at' => null,
                'fcm_token' => $user->fcm_token,
                'fcm_status' => $fcmStatus,
                'fcm_error' => $fcmError,
            ]);
        }

        $appNotification->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);

        return $appNotification;
    }
}
