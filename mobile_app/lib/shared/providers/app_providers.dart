import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/models.dart';
import 'auth_provider.dart';

final campaignsProvider = FutureProvider<List<Campaign>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/campaigns');
  if (response.data['success'] == true) {
    final list = response.data['data'] as List;
    return list.map((c) => Campaign.fromJson(c)).toList();
  }
  return [];
});

final myLinksProvider = FutureProvider<List<AffiliateLink>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/links');
  if (response.data['success'] == true) {
    final list = response.data['data'] as List;
    return list.map((l) => AffiliateLink.fromJson(l)).toList();
  }
  return [];
});

final walletProvider = FutureProvider<WalletData>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/wallet/summary');
  if (response.data['success'] == true) {
    return WalletData.fromJson(response.data['data']);
  }
  throw Exception(response.data['message'] ?? 'Failed to load wallet data');
});

final clickReportsProvider = FutureProvider<List<ClickReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports/clicks');
  if (response.data['success'] == true) {
    final list = response.data['data'] as List;
    return list.map((c) => ClickReport.fromJson(c)).toList();
  }
  return [];
});

final conversionReportsProvider = FutureProvider<List<ConversionReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports/conversions');
  if (response.data['success'] == true) {
    final list = response.data['data'] as List;
    return list.map((c) => ConversionReport.fromJson(c)).toList();
  }
  return [];
});

final referralDashboardProvider = FutureProvider<ReferralDashboardData>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/referrals/dashboard');
  if (response.data['success'] == true) {
    return ReferralDashboardData.fromJson(response.data['data']);
  }
  throw Exception(response.data['message'] ?? 'Failed to load referral dashboard');
});

final teamMembersProvider = FutureProvider<List<TeamMember>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/referrals/team');
  if (response.data['success'] == true) {
    final list = response.data['data'] as List;
    return list.map((m) => TeamMember.fromJson(m)).toList();
  }
  return [];
});
