import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/api_client.dart';
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

final missingTicketsProvider = FutureProvider<List<MissingConversionTicket>>((ref) async {
  final api = ref.watch(apiClientProvider);
  try {
    final response = await api.get('/tickets');
    final data = response.data;
    final rawList = (data is Map && data['data'] != null)
        ? data['data']
        : (data is Map && data['tickets'] != null)
            ? data['tickets']
            : (data is List ? data : []);
    if (rawList is List) {
      return rawList
          .whereType<Map>()
          .map((t) => MissingConversionTicket.fromJson(Map<String, dynamic>.from(t)))
          .toList();
    }
  } catch (e) {
    debugPrint('Error fetching missing tickets: $e');
  }
  return [];
});

class DealsPaginationState {
  final List<DealItem> deals;
  final bool isLoading;
  final bool isLoadingMore;
  final bool hasMore;
  final int currentPage;
  final int total;
  final String? error;

  const DealsPaginationState({
    this.deals = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.hasMore = true,
    this.currentPage = 1,
    this.total = 0,
    this.error,
  });

  DealsPaginationState copyWith({
    List<DealItem>? deals,
    bool? isLoading,
    bool? isLoadingMore,
    bool? hasMore,
    int? currentPage,
    int? total,
    String? error,
  }) {
    return DealsPaginationState(
      deals: deals ?? this.deals,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      hasMore: hasMore ?? this.hasMore,
      currentPage: currentPage ?? this.currentPage,
      total: total ?? this.total,
      error: error,
    );
  }
}

class DealsPaginationNotifier extends StateNotifier<DealsPaginationState> {
  final ApiClient _api;

  DealsPaginationNotifier(this._api) : super(const DealsPaginationState()) {
    fetchInitial();
  }

  Future<void> fetchInitial() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _api.get('/deals', queryParameters: {
        'page': 1,
        'per_page': 20,
      });
      final data = response.data;
      final rawList = (data is Map && data['data'] != null)
          ? data['data']
          : (data is List ? data : []);

      final List<DealItem> items = [];
      if (rawList is List) {
        for (var d in rawList) {
          if (d is Map) {
            items.add(DealItem.fromJson(Map<String, dynamic>.from(d)));
          }
        }
      }

      int total = items.length;
      int lastPage = 1;
      if (data is Map && data['pagination'] is Map) {
        final p = data['pagination'];
        total = (p['total'] as num?)?.toInt() ?? items.length;
        lastPage = (p['last_page'] as num?)?.toInt() ?? 1;
      }

      state = DealsPaginationState(
        deals: items,
        isLoading: false,
        isLoadingMore: false,
        hasMore: 1 < lastPage,
        currentPage: 1,
        total: total,
      );
    } catch (e) {
      debugPrint('Error fetching initial deals: $e');
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> loadNextSlot() async {
    if (state.isLoading || state.isLoadingMore || !state.hasMore) return;

    final nextPage = state.currentPage + 1;
    state = state.copyWith(isLoadingMore: true);

    try {
      final response = await _api.get('/deals', queryParameters: {
        'page': nextPage,
        'per_page': 20,
      });
      final data = response.data;
      final rawList = (data is Map && data['data'] != null)
          ? data['data']
          : (data is List ? data : []);

      final List<DealItem> newItems = [];
      if (rawList is List) {
        for (var d in rawList) {
          if (d is Map) {
            newItems.add(DealItem.fromJson(Map<String, dynamic>.from(d)));
          }
        }
      }

      int lastPage = nextPage;
      int total = state.total;
      if (data is Map && data['pagination'] is Map) {
        final p = data['pagination'];
        total = (p['total'] as num?)?.toInt() ?? total;
        lastPage = (p['last_page'] as num?)?.toInt() ?? nextPage;
      }

      // Merge avoiding duplicates by ID
      final existingIds = state.deals.map((d) => d.id).toSet();
      final deduplicated = [
        ...state.deals,
        ...newItems.where((d) => !existingIds.contains(d.id)),
      ];

      state = state.copyWith(
        deals: deduplicated,
        isLoadingMore: false,
        currentPage: nextPage,
        hasMore: nextPage < lastPage && newItems.isNotEmpty,
        total: total,
      );
    } catch (e) {
      debugPrint('Error loading next deals slot: $e');
      state = state.copyWith(isLoadingMore: false);
    }
  }

  Future<void> refresh() async {
    await fetchInitial();
  }
}

final dealsPaginationProvider = StateNotifierProvider<DealsPaginationNotifier, DealsPaginationState>((ref) {
  final api = ref.watch(apiClientProvider);
  return DealsPaginationNotifier(api);
});

final dealsProvider = Provider<List<DealItem>>((ref) {
  return ref.watch(dealsPaginationProvider).deals;
});



