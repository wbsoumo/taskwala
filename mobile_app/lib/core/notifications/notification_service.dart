import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:go_router/go_router.dart';
import '../network/api_client.dart';

@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  if (kDebugMode) {
    debugPrint("Handling background FCM message: ${message.messageId}");
  }
}

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  ApiClient? _apiClient;
  GoRouter? _router;
  String? _fcmToken;

  String? get currentFcmToken => _fcmToken;

  Future<void> initialize({required ApiClient apiClient, required GoRouter router}) async {
    _apiClient = apiClient;
    _router = router;

    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

      final messaging = FirebaseMessaging.instance;

      // Request permission on iOS/Android 13+
      final settings = await messaging.requestPermission(
        alert: true,
        announcement: false,
        badge: true,
        carPlay: false,
        criticalAlert: false,
        provisional: false,
        sound: true,
      );

      if (kDebugMode) {
        debugPrint('FCM Authorization status: ${settings.authorizationStatus}');
      }

      // Fetch FCM Device Token
      _fcmToken = await messaging.getToken();
      if (kDebugMode) {
        debugPrint('Firebase FCM Device Token: $_fcmToken');
      }

      if (_fcmToken != null) {
        await syncTokenWithBackend(_fcmToken!);
      }

      // Token refresh listener
      messaging.onTokenRefresh.listen((newToken) {
        _fcmToken = newToken;
        if (kDebugMode) {
          debugPrint('FCM Token Refreshed: $newToken');
        }
        syncTokenWithBackend(newToken);
      });

      // Foreground message listener
      FirebaseMessaging.onMessage.listen((RemoteMessage message) {
        if (kDebugMode) {
          debugPrint('Foreground FCM notification received: ${message.notification?.title}');
        }
      });

      // Message click listener when app is in background
      FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
        _handleNotificationDeeplink(message);
      });

      // App opened from terminated state by tapping notification
      final initialMessage = await messaging.getInitialMessage();
      if (initialMessage != null) {
        _handleNotificationDeeplink(initialMessage);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Firebase NotificationService initialization error: $e');
      }
    }
  }

  Future<void> syncTokenWithBackend(String token) async {
    if (_apiClient == null) return;
    try {
      await _apiClient!.post('/user/fcm-token', data: {
        'fcm_token': token,
      });
      if (kDebugMode) {
        debugPrint('Successfully registered FCM token with Taskwala backend.');
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('FCM Token sync error: $e');
      }
    }
  }

  void _handleNotificationDeeplink(RemoteMessage message) {
    final deeplink = message.data['deeplink']?.toString().trim();
    if (deeplink == null || deeplink.isEmpty || _router == null) return;

    String targetRoute = deeplink;
    if (targetRoute.startsWith('taskwala://')) {
      targetRoute = targetRoute.replaceFirst('taskwala://', '/');
    }
    if (!targetRoute.startsWith('/')) {
      targetRoute = '/$targetRoute';
    }

    try {
      _router!.push(targetRoute);
    } catch (e) {
      if (kDebugMode) {
        debugPrint('Deeplink navigation failed for route: $targetRoute, error: $e');
      }
    }
  }
}
