<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AppNotification::with(['admin'])
            ->withCount([
                'userNotifications as total_recipients',
                'userNotifications as read_count' => function ($q) {
                    $q->where('is_read', true);
                },
                'userNotifications as unread_count' => function ($q) {
                    $q->where('is_read', false);
                },
            ])
            ->latest('id')
            ->paginate(15);

        $totalUsers = User::count();
        $usersWithToken = User::whereNotNull('fcm_token')->count();
        $totalSentNotifications = AppNotification::sum('sent_count');

        return view('admin.notifications.index', compact(
            'notifications',
            'totalUsers',
            'usersWithToken',
            'totalSentNotifications'
        ));
    }

    public function create(Request $request)
    {
        $selectedUserId = $request->query('user_id');
        $users = User::select('id', 'name', 'email', 'mobile_number', 'fcm_token')
            ->orderBy('name')
            ->get();

        return view('admin.notifications.create', compact('users', 'selectedUserId'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'deeplink' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'target_type' => ['required', 'in:all,selected_users'],
            'user_ids' => ['required_if:target_type,selected_users', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $targetType = $request->target_type;
        $users = $targetType === 'all'
            ? User::where('status', 'active')->get()
            : User::whereIn('id', $request->user_ids)->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No users found matching the selected target criteria.')->withInput();
        }

        $appNotification = AppNotification::create([
            'title' => $request->title,
            'body' => $request->body,
            'deeplink' => $request->deeplink ?: null,
            'image_url' => $request->image_url ?: null,
            'target_type' => $targetType,
            'total_targeted' => $users->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by_admin_id' => auth('admin')->id(),
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $fcmStatus = 'no_token';
            $fcmError = null;

            if (!empty($user->fcm_token)) {
                $fcmResult = $firebase->sendNotification(
                    fcmToken: $user->fcm_token,
                    title: $request->title,
                    body: $request->body,
                    deeplink: $request->deeplink,
                    imageUrl: $request->image_url
                );

                if (!empty($fcmResult['success'])) {
                    $fcmStatus = 'sent';
                    $sentCount++;
                } else {
                    $fcmStatus = 'failed';
                    $fcmError = $fcmResult['error'] ?? 'Delivery failed';
                    $failedCount++;
                }
            }

            UserNotification::create([
                'app_notification_id' => $appNotification->id,
                'user_id' => $user->id,
                'title' => $request->title,
                'body' => $request->body,
                'deeplink' => $request->deeplink ?: null,
                'image_url' => $request->image_url ?: null,
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

        return redirect()->route('admin.notifications.show', $appNotification->id)
            ->with('success', "Notification created for {$users->count()} users ({$sentCount} FCM push messages dispatched).");
    }

    public function show(Request $request, $id)
    {
        $notification = AppNotification::with(['admin'])->findOrFail($id);

        $query = UserNotification::with('user')
            ->where('app_notification_id', $notification->id);

        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $query->where('is_read', true);
            } elseif ($request->status === 'unread') {
                $query->where('is_read', false);
            }
        }

        if ($request->filled('fcm_status')) {
            $query->where('fcm_status', $request->fcm_status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        $deliveries = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => UserNotification::where('app_notification_id', $notification->id)->count(),
            'read' => UserNotification::where('app_notification_id', $notification->id)->where('is_read', true)->count(),
            'unread' => UserNotification::where('app_notification_id', $notification->id)->where('is_read', false)->count(),
            'fcm_sent' => UserNotification::where('app_notification_id', $notification->id)->where('fcm_status', 'sent')->count(),
            'fcm_failed' => UserNotification::where('app_notification_id', $notification->id)->where('fcm_status', 'failed')->count(),
            'fcm_no_token' => UserNotification::where('app_notification_id', $notification->id)->where('fcm_status', 'no_token')->count(),
        ];

        return view('admin.notifications.show', compact('notification', 'deliveries', 'stats'));
    }

    public function toggleUserReadStatus(Request $request, $id)
    {
        $userNotification = UserNotification::findOrFail($id);
        if ($userNotification->is_read) {
            $userNotification->markAsUnread();
            $msg = 'Notification marked as Unread for this user.';
        } else {
            $userNotification->markAsRead();
            $msg = 'Notification marked as Read for this user.';
        }

        return back()->with('success', $msg);
    }

    public function registeredUsers(Request $request)
    {
        $query = User::withCount([
            'notifications as total_received',
            'notifications as total_read' => function ($q) {
                $q->where('is_read', true);
            },
        ]);

        if ($request->filled('token_status')) {
            if ($request->token_status === 'has_token') {
                $query->whereNotNull('fcm_token');
            } elseif ($request->token_status === 'no_token') {
                $query->whereNull('fcm_token');
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('public_id', 'like', "%{$search}%");
            });
        }

        $users = $query->latest('id')->paginate(20)->withQueryString();

        $stats = [
            'total_registered' => User::count(),
            'with_fcm_token' => User::whereNotNull('fcm_token')->count(),
            'without_token' => User::whereNull('fcm_token')->count(),
        ];

        return view('admin.notifications.users', compact('users', 'stats'));
    }

    public function retryUserFcm(Request $request, $id, FirebaseService $firebase)
    {
        $userNotification = UserNotification::with(['user', 'appNotification'])->findOrFail($id);
        $user = $userNotification->user;

        $token = $userNotification->fcm_token ?: $user?->fcm_token;
        if (empty($token)) {
            return back()->with('error', 'User does not have an active FCM device token.');
        }

        $result = $firebase->sendNotification(
            fcmToken: $token,
            title: $userNotification->title,
            body: $userNotification->body,
            deeplink: $userNotification->deeplink,
            imageUrl: $userNotification->image_url
        );

        if (!empty($result['success'])) {
            $userNotification->update([
                'fcm_token' => $token,
                'fcm_status' => 'sent',
                'fcm_error' => null,
            ]);

            if ($userNotification->app_notification_id) {
                $appNotif = $userNotification->appNotification;
                if ($appNotif) {
                    $appNotif->update([
                        'sent_count' => UserNotification::where('app_notification_id', $appNotif->id)->where('fcm_status', 'sent')->count(),
                        'failed_count' => UserNotification::where('app_notification_id', $appNotif->id)->where('fcm_status', 'failed')->count(),
                    ]);
                }
            }

            return back()->with('success', "FCM push successfully delivered to {$user?->name} ({$result['message_id']}).");
        }

        $error = $result['error'] ?? 'Delivery failed';
        $userNotification->update([
            'fcm_status' => 'failed',
            'fcm_error' => $error,
        ]);

        return back()->with('error', "FCM push retry failed: {$error}");
    }

    public function retryFailed(Request $request, $id, FirebaseService $firebase)
    {
        $notification = AppNotification::findOrFail($id);
        $failedDeliveries = UserNotification::with('user')
            ->where('app_notification_id', $notification->id)
            ->where('fcm_status', 'failed')
            ->get();

        if ($failedDeliveries->isEmpty()) {
            return back()->with('info', 'No failed FCM deliveries to retry.');
        }

        $retried = 0;
        $succeeded = 0;

        foreach ($failedDeliveries as $item) {
            $token = $item->fcm_token ?: $item->user?->fcm_token;
            if (empty($token)) continue;

            $retried++;
            $result = $firebase->sendNotification(
                fcmToken: $token,
                title: $item->title,
                body: $item->body,
                deeplink: $item->deeplink,
                imageUrl: $item->image_url
            );

            if (!empty($result['success'])) {
                $succeeded++;
                $item->update([
                    'fcm_token' => $token,
                    'fcm_status' => 'sent',
                    'fcm_error' => null,
                ]);
            } else {
                $item->update([
                    'fcm_status' => 'failed',
                    'fcm_error' => $result['error'] ?? 'Delivery failed',
                ]);
            }
        }

        $notification->update([
            'sent_count' => UserNotification::where('app_notification_id', $notification->id)->where('fcm_status', 'sent')->count(),
            'failed_count' => UserNotification::where('app_notification_id', $notification->id)->where('fcm_status', 'failed')->count(),
        ]);

        return back()->with('success', "Retried {$retried} recipients: {$succeeded} delivered successfully.");
    }
}

