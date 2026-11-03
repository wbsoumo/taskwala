import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/models.dart';
import 'auth_provider.dart';

final campaignsProvider = FutureProvider<List<Campaign>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/campaigns');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['campaigns'] != null)
            ? data['campaigns']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((c) => Campaign.fromJson(Map<String, dynamic>.from(c)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching campaigns: $e');
  }
  return [];
});

final myLinksProvider = FutureProvider<List<AffiliateLink>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/links');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['links'] != null)
            ? data['links']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((l) => AffiliateLink.fromJson(Map<String, dynamic>.from(l)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching links: $e');
  }
  return [];
});

final walletProvider = FutureProvider<WalletData>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/wallet');
    final data = response.data;
    if (data is Map) {
      final walletMap = data['wallet'] is Map
          ? Map<String, dynamic>.from(data['wallet'])
          : (data['data'] is Map ? Map<String, dynamic>.from(data['data']) : Map<String, dynamic>.from(data));
      final txList = (data['transactions'] is Map && data['transactions']['data'] is List)
          ? data['transactions']['data']
          : (data['transactions'] is List ? data['transactions'] : []);

      walletMap['transactions'] = txList;

      if (data['min_withdrawal_threshold'] != null) {
        walletMap['min_withdrawal_threshold'] = data['min_withdrawal_threshold'];
      }
      if (data['payout_requests'] != null) {
        walletMap['payout_requests'] = data['payout_requests'];
      }

      return WalletData.fromJson(walletMap);
    }
  } catch (e) {
    debugPrint('Error fetching wallet: $e');
  }
  return WalletData(
    availableBalance: 0,
    pendingBalance: 0,
    approvedBalance: 0,
    totalEarnings: 0,
    totalPaid: 0,
    upiStatus: 'not_added',
    minWithdrawalThreshold: 500.0,
    transactions: [],
    payoutRequests: [],
  );
});

final payoutRequestsProvider = FutureProvider<List<PayoutRequestItem>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/wallet/payout-requests');
    final data = response.data;
    final dynamic rawData = data is Map ? (data['data'] ?? data) : data;
    final List<dynamic> rawList = (rawData is Map && rawData['data'] is List)
        ? rawData['data']
        : (rawData is List ? rawData : []);

    if (rawList.isNotEmpty) {
      return rawList
          .whereType<Map>()
          .map((p) => PayoutRequestItem.fromJson(Map<String, dynamic>.from(p)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching payout requests: $e');
  }

  // Fallback to walletProvider's payoutRequests if any
  final wallet = ref.watch(walletProvider).asData?.value;
  return wallet?.payoutRequests ?? [];
});

final clickReportsProvider = FutureProvider<List<ClickReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/reports/clicks');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['clicks'] != null)
            ? data['clicks']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((c) => ClickReport.fromJson(Map<String, dynamic>.from(c)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching clicks: $e');
  }
  return [];
});

final conversionReportsProvider = FutureProvider<List<ConversionReport>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/reports/conversions');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['conversions'] != null)
            ? data['conversions']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((c) => ConversionReport.fromJson(Map<String, dynamic>.from(c)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching conversions: $e');
  }
  return [];
});

final referralDashboardProvider = FutureProvider<ReferralDashboardData>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/referrals/dashboard');
    final data = response.data;
    final rawObj = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map ? data : {});
    return ReferralDashboardData.fromJson(Map<String, dynamic>.from(rawObj));
  } catch (e) {
    debugPrint('Error fetching referral dashboard: $e');
  }
  return ReferralDashboardData(
    referralCode: '',
    referralLink: '',
    totalReferred: 0,
    registeredCount: 0,
    activeCount: 0,
    convertedCount: 0,
    totalEarnings: 0,
    pendingEarnings: 0,
    paidEarnings: 0,
    currentRuleSummary: 'Earn rewards for each friend who registers and activates.',
  );
});

final teamMembersProvider = FutureProvider<List<TeamMember>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/referrals/team');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['team'] != null)
            ? data['team']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((m) => TeamMember.fromJson(Map<String, dynamic>.from(m)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching team members: $e');
  }
  return [];
});
