import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/models.dart';
import 'auth_provider.dart';

final campaignsProvider = FutureProvider<List<Campaign>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/campaigns');
  final data = response.data;
  final rawList = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map && data['campaigns'] != null)
          ? data['campaigns']
          : (data is List ? data : []);
  if (rawList is List) {
    return rawList.map((c) => Campaign.fromJson(c)).toList();
  }
  return [];
});

final myLinksProvider = FutureProvider<List<AffiliateLink>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/links');
  final data = response.data;
  final rawList = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map && data['links'] != null)
          ? data['links']
          : (data is List ? data : []);
  if (rawList is List) {
    return rawList.map((l) => AffiliateLink.fromJson(l)).toList();
  }
  return [];
});

final walletProvider = FutureProvider<WalletData>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/wallet/summary');
  final data = response.data;
  final rawObj = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map ? data : {});
  return WalletData.fromJson(Map<String, dynamic>.from(rawObj));
});

final clickReportsProvider = FutureProvider<List<ClickReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports/clicks');
  final data = response.data;
  final rawList = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map && data['clicks'] != null)
          ? data['clicks']
          : (data is List ? data : []);
  if (rawList is List) {
    return rawList.map((c) => ClickReport.fromJson(c)).toList();
  }
  return [];
});

final conversionReportsProvider = FutureProvider<List<ConversionReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/reports/conversions');
  final data = response.data;
  final rawList = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map && data['conversions'] != null)
          ? data['conversions']
          : (data is List ? data : []);
  if (rawList is List) {
    return rawList.map((c) => ConversionReport.fromJson(c)).toList();
  }
  return [];
});

final referralDashboardProvider = FutureProvider<ReferralDashboardData>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/referrals/dashboard');
  final data = response.data;
  final rawObj = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map ? data : {});
  return ReferralDashboardData.fromJson(Map<String, dynamic>.from(rawObj));
});

final teamMembersProvider = FutureProvider<List<TeamMember>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/referrals/team');
  final data = response.data;
  final rawList = (data is Map && data['data'] != null)
      ? data['data']
      : (data is Map && data['team'] != null)
          ? data['team']
          : (data is List ? data : []);
  if (rawList is List) {
    return rawList.map((m) => TeamMember.fromJson(m)).toList();
  }
  return [];
});
