import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/api_client.dart';
import '../../core/storage/secure_storage_service.dart';
import '../../core/notifications/notification_service.dart';
import '../models/models.dart';

class AuthResult {
  final bool success;
  final bool requiresVerification;
  final String? email;
  final String? message;
  final String? debugCode;

  const AuthResult({
    required this.success,
    this.requiresVerification = false,
    this.email,
    this.message,
    this.debugCode,
  });
}

class AuthState {
  final User? user;
  final bool isAuthenticated;
  final bool isLoading;
  final String? error;

  AuthState({
    this.user,
    this.isAuthenticated = false,
    this.isLoading = false,
    this.error,
  });

  AuthState copyWith({
    User? user,
    bool? isAuthenticated,
    bool? isLoading,
    String? error,
  }) {
    return AuthState(
      user: user ?? this.user,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final ApiClient _apiClient;
  final SecureStorageService _storage;

  AuthNotifier(this._apiClient, this._storage) : super(AuthState()) {
    checkAuthStatus();
  }

  Future<void> checkAuthStatus() async {
    state = state.copyWith(isLoading: true);
    final token = await _storage.getToken();
    if (token != null) {
      _apiClient.setAuthToken(token);
      await fetchProfile();
      final fcm = NotificationService().currentFcmToken;
      if (fcm != null && fcm.isNotEmpty) {
        NotificationService().syncTokenWithBackend(fcm);
      }
    } else {
      state = state.copyWith(isLoading: false, isAuthenticated: false);
    }
  }

  Future<AuthResult> login(String login, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final fcmToken = NotificationService().currentFcmToken;
      dynamic response;
      try {
        response = await _apiClient.post('/auth/login', data: {
          'login': login,
          'password': password,
          if (fcmToken != null && fcmToken.isNotEmpty) 'fcm_token': fcmToken,
        });
      } on DioException catch (dioErr) {
        // If server errored with 500 when fcm_token was included, fallback to clean login
        if (fcmToken != null && fcmToken.isNotEmpty && (dioErr.response?.statusCode == 500 || dioErr.response == null)) {
          response = await _apiClient.post('/auth/login', data: {
            'login': login,
            'password': password,
          });
        } else {
          rethrow;
        }
      }

      final data = response.data;
      if (data != null && (data['token'] != null || (data['data'] != null && data['data']['token'] != null))) {
        final token = data['token'] ?? data['data']['token'];
        final userData = data['user'] ?? data['data']?['user'] ?? {};
        await _storage.saveToken(token);
        _apiClient.setAuthToken(token);
        final user = User.fromJson(userData);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        if (fcmToken != null && fcmToken.isNotEmpty) {
          NotificationService().syncTokenWithBackend(fcmToken);
        }
        return const AuthResult(success: true);
      } else {
        state = state.copyWith(
          isLoading: false,
          error: data?['message'] ?? 'Login failed',
        );
        return AuthResult(
          success: false,
          message: data?['message'] ?? 'Login failed',
        );
      }
    } on DioException catch (e) {
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        if (resData['requires_verification'] == true) {
          state = state.copyWith(isLoading: false, error: null);
          return AuthResult(
            success: false,
            requiresVerification: true,
            email: resData['email']?.toString() ?? login,
            message: resData['message']?.toString(),
            debugCode: resData['debug_code']?.toString(),
          );
        }
        final msg = resData['message']?.toString() ?? 'Invalid email/mobile or password credentials.';
        state = state.copyWith(isLoading: false, error: msg);
        return AuthResult(success: false, message: msg);
      }
      state = state.copyWith(
        isLoading: false,
        error: 'Network error or invalid credentials',
      );
      return const AuthResult(success: false, message: 'Network error or invalid credentials');
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Network error or invalid credentials',
      );
      return const AuthResult(success: false, message: 'Network error or invalid credentials');
    }
  }

  Future<AuthResult> register({
    required String name,
    required String email,
    required String mobile,
    required String password,
    required String passwordConfirmation,
    String? referralCode,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _apiClient.post('/auth/register', data: {
        'name': name,
        'email': email,
        'mobile_number': mobile,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (referralCode != null && referralCode.isNotEmpty) 'referral_code': referralCode,
      });

      final data = response.data;
      if (data != null && data['requires_verification'] == true) {
        state = state.copyWith(isLoading: false, error: null);
        return AuthResult(
          success: false,
          requiresVerification: true,
          email: data['email']?.toString() ?? email,
          message: data['message']?.toString(),
          debugCode: data['debug_code']?.toString(),
        );
      } else if (data != null && (data['token'] != null || (data['data'] != null && data['data']['token'] != null))) {
        final token = data['token'] ?? data['data']['token'];
        final userData = data['user'] ?? data['data']?['user'] ?? {};
        await _storage.saveToken(token);
        _apiClient.setAuthToken(token);
        final user = User.fromJson(userData);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        return const AuthResult(success: true);
      } else {
        state = state.copyWith(
          isLoading: false,
          error: data?['message'] ?? 'Registration failed',
        );
        return AuthResult(success: false, message: data?['message'] ?? 'Registration failed');
      }
    } on DioException catch (e) {
      String msg = 'Registration failed. Check details.';
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        msg = resData['message']?.toString() ??
            (resData['errors'] is Map ? (resData['errors'] as Map).values.first?.toString() : null) ??
            msg;
      }
      state = state.copyWith(isLoading: false, error: msg);
      return AuthResult(success: false, message: msg);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Registration failed. Check details.',
      );
      return const AuthResult(success: false, message: 'Registration failed. Check details.');
    }
  }

  Future<AuthResult> verifyEmail({
    required String email,
    required String otp,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final fcmToken = NotificationService().currentFcmToken;
      final response = await _apiClient.post('/auth/verify-email', data: {
        'email': email,
        'otp': otp,
        if (fcmToken != null && fcmToken.isNotEmpty) 'fcm_token': fcmToken,
      });

      final data = response.data;
      if (data != null && data['token'] != null) {
        final token = data['token'];
        final userData = data['user'] ?? {};
        await _storage.saveToken(token);
        _apiClient.setAuthToken(token);
        final user = User.fromJson(userData);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        if (fcmToken != null && fcmToken.isNotEmpty) {
          NotificationService().syncTokenWithBackend(fcmToken);
        }
        return AuthResult(success: true, message: data['message']);
      } else {
        final msg = data?['message'] ?? 'Verification failed';
        state = state.copyWith(isLoading: false, error: msg);
        return AuthResult(success: false, message: msg);
      }
    } on DioException catch (e) {
      String msg = 'Invalid or expired verification code.';
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        msg = resData['message']?.toString() ?? msg;
      }
      state = state.copyWith(isLoading: false, error: msg);
      return AuthResult(success: false, message: msg);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'Verification failed');
      return const AuthResult(success: false, message: 'Verification failed. Please check connection.');
    }
  }

  Future<Map<String, dynamic>> resendVerificationEmail(String email) async {
    try {
      final response = await _apiClient.post('/auth/resend-verification-email', data: {
        'email': email,
      });
      final data = response.data;
      if (data is Map<String, dynamic>) {
        return data;
      }
      return {'success': true, 'message': 'Verification code resent successfully.'};
    } on DioException catch (e) {
      String msg = 'Failed to resend code.';
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        msg = resData['message']?.toString() ?? msg;
      }
      return {'success': false, 'message': msg};
    } catch (e) {
      return {'success': false, 'message': 'Network error while resending code.'};
    }
  }

  Future<void> fetchProfile() async {
    try {
      final response = await _apiClient.get('/user/profile');
      final data = response.data;
      if (data != null && (data['user'] != null || data['data'] != null)) {
        final userData = data['user'] ?? data['data'];
        final user = User.fromJson(userData);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
      } else {
        await logout();
      }
    } catch (e) {
      await logout();
    }
  }

  Future<void> logout() async {
    try {
      await _apiClient.post('/auth/logout');
    } catch (_) {}
    await _storage.deleteToken();
    _apiClient.setAuthToken(null);
    state = AuthState(isAuthenticated: false, isLoading: false);
  }
}

final secureStorageProvider = Provider((ref) => SecureStorageService());

final apiClientProvider = Provider((ref) {
  final storage = ref.watch(secureStorageProvider);
  return ApiClient(storage: storage);
});

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  final storage = ref.watch(secureStorageProvider);
  return AuthNotifier(apiClient, storage);
});
