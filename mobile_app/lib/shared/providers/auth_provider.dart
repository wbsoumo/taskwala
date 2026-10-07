import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/api_client.dart';
import '../../core/storage/secure_storage_service.dart';
import '../models/models.dart';

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
    } else {
      state = state.copyWith(isLoading: false, isAuthenticated: false);
    }
  }

  Future<bool> login(String login, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _apiClient.post('/auth/login', data: {
        'login': login,
        'password': password,
      });

      if (response.data['success'] == true) {
        final token = response.data['data']['token'];
        await _storage.saveToken(token);
        _apiClient.setAuthToken(token);
        final user = User.fromJson(response.data['data']['user']);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          error: response.data['message'] ?? 'Login failed',
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Network error or invalid credentials',
      );
      return false;
    }
  }

  Future<bool> register({
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
        'mobile': mobile,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (referralCode != null && referralCode.isNotEmpty) 'referral_code': referralCode,
      });

      if (response.data['success'] == true) {
        final token = response.data['data']['token'];
        await _storage.saveToken(token);
        _apiClient.setAuthToken(token);
        final user = User.fromJson(response.data['data']['user']);
        state = state.copyWith(
          user: user,
          isAuthenticated: true,
          isLoading: false,
        );
        return true;
      } else {
        state = state.copyWith(
          isLoading: false,
          error: response.data['message'] ?? 'Registration failed',
        );
        return false;
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'Registration failed. Check details.',
      );
      return false;
    }
  }

  Future<void> fetchProfile() async {
    try {
      final response = await _apiClient.get('/user/profile');
      if (response.data['success'] == true) {
        final user = User.fromJson(response.data['data']);
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
