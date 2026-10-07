class User {
  final String publicId;
  final String name;
  final String email;
  final String? mobile;
  final String? referralCode;
  final String status;
  final String kycStatus;
  final bool payoutEnabled;

  User({
    required this.publicId,
    required this.name,
    required this.email,
    this.mobile,
    this.referralCode,
    required this.status,
    required this.kycStatus,
    required this.payoutEnabled,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      publicId: json['public_id'] ?? json['id']?.toString() ?? '',
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      mobile: json['mobile_number'] ?? json['mobile'],
      referralCode: json['referral_code'],
      status: json['status'] ?? 'active',
      kycStatus: json['kyc_status'] ?? 'approved',
      payoutEnabled: json['payout_enabled'] ?? true,
    );
  }

  Map<String, dynamic> toJson() => {
        'public_id': publicId,
        'name': name,
        'email': email,
        'mobile_number': mobile,
        'referral_code': referralCode,
        'status': status,
        'kyc_status': kycStatus,
        'payout_enabled': payoutEnabled,
      };
}

class Campaign {
  final int id;
  final String name;
  final String? logo;
  final String category;
  final double advertiserPayout;
  final double affiliatePayout;
  final String conversionType;
  final String status;
  final String? description;
  final String? terms;
  final String? eligibility;

  Campaign({
    required this.id,
    required this.name,
    this.logo,
    required this.category,
    required this.advertiserPayout,
    required this.affiliatePayout,
    required this.conversionType,
    required this.status,
    this.description,
    this.terms,
    this.eligibility,
  });

  factory Campaign.fromJson(Map<String, dynamic> json) {
    return Campaign(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] ?? '',
      logo: json['logo'],
      category: json['category'] ?? 'General',
      advertiserPayout: (json['advertiser_payout'] ?? json['payout_amount'] ?? 0).toDouble(),
      affiliatePayout: (json['affiliate_payout'] ?? json['user_payout'] ?? json['payout_amount'] ?? 0).toDouble(),
      conversionType: json['conversion_type'] ?? 'CPA',
      status: json['status'] ?? 'active',
      description: json['description'],
      terms: json['terms'],
      eligibility: json['eligibility'],
    );
  }
}

class AffiliateLink {
  final int id;
  final int campaignId;
  final String campaignName;
  final String secureUrl;
  final String code;
  final double customerPayout;
  final double affiliateCommission;
  final int clicks;
  final int conversions;
  final String createdAt;

  AffiliateLink({
    required this.id,
    required this.campaignId,
    required this.campaignName,
    required this.secureUrl,
    required this.code,
    required this.customerPayout,
    required this.affiliateCommission,
    required this.clicks,
    required this.conversions,
    required this.createdAt,
  });

  factory AffiliateLink.fromJson(Map<String, dynamic> json) {
    return AffiliateLink(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      campaignId: json['campaign_id'] is int ? json['campaign_id'] : int.parse(json['campaign_id'].toString()),
      campaignName: json['campaign_name'] ?? json['campaign']?['name'] ?? 'Campaign',
      secureUrl: json['secure_url'] ?? json['tracking_url'] ?? '',
      code: json['code'] ?? '',
      customerPayout: (json['customer_payout'] ?? 0).toDouble(),
      affiliateCommission: (json['affiliate_commission'] ?? 0).toDouble(),
      clicks: json['clicks_count'] ?? json['clicks'] ?? 0,
      conversions: json['conversions_count'] ?? json['conversions'] ?? 0,
      createdAt: json['created_at'] ?? '',
    );
  }
}

class ClickReport {
  final int id;
  final String campaignName;
  final String linkCode;
  final String ipAddress;
  final String clickedAt;

  ClickReport({
    required this.id,
    required this.campaignName,
    required this.linkCode,
    required this.ipAddress,
    required this.clickedAt,
  });

  factory ClickReport.fromJson(Map<String, dynamic> json) {
    return ClickReport(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      campaignName: json['campaign_name'] ?? json['campaign']?['name'] ?? 'Campaign',
      linkCode: json['link_code'] ?? json['link']?['code'] ?? 'N/A',
      ipAddress: json['ip_address'] ?? 'Masked',
      clickedAt: json['created_at'] ?? json['clicked_at'] ?? '',
    );
  }
}

class ConversionReport {
  final int id;
  final String campaignName;
  final String referenceId;
  final double customerPayout;
  final double commission;
  final String status;
  final String payoutStatus;
  final String createdAt;

  ConversionReport({
    required this.id,
    required this.campaignName,
    required this.referenceId,
    required this.customerPayout,
    required this.commission,
    required this.status,
    required this.payoutStatus,
    required this.createdAt,
  });

  factory ConversionReport.fromJson(Map<String, dynamic> json) {
    return ConversionReport(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      campaignName: json['campaign_name'] ?? json['campaign']?['name'] ?? 'Campaign',
      referenceId: json['reference_id'] ?? json['click_id'] ?? 'REF-${json['id']}',
      customerPayout: (json['customer_payout'] ?? 0).toDouble(),
      commission: (json['commission'] ?? json['affiliate_commission'] ?? 0).toDouble(),
      status: json['status'] ?? 'pending',
      payoutStatus: json['payout_status'] ?? 'unpaid',
      createdAt: json['created_at'] ?? '',
    );
  }
}

class WalletData {
  final double availableBalance;
  final double pendingBalance;
  final double approvedBalance;
  final double totalEarnings;
  final double totalPaid;
  final String? upiId;
  final String upiStatus;
  final List<WalletTransaction> transactions;

  WalletData({
    required this.availableBalance,
    required this.pendingBalance,
    required this.approvedBalance,
    required this.totalEarnings,
    required this.totalPaid,
    this.upiId,
    required this.upiStatus,
    required this.transactions,
  });

  factory WalletData.fromJson(Map<String, dynamic> json) {
    var txList = (json['transactions'] as List? ?? [])
        .map((t) => WalletTransaction.fromJson(t))
        .toList();

    return WalletData(
      availableBalance: (json['available_balance'] ?? json['balance'] ?? 0).toDouble(),
      pendingBalance: (json['pending_balance'] ?? 0).toDouble(),
      approvedBalance: (json['approved_balance'] ?? 0).toDouble(),
      totalEarnings: (json['total_earnings'] ?? 0).toDouble(),
      totalPaid: (json['total_paid'] ?? 0).toDouble(),
      upiId: json['upi_id'],
      upiStatus: json['upi_status'] ?? 'not_added',
      transactions: txList,
    );
  }
}

class WalletTransaction {
  final int id;
  final String type;
  final double amount;
  final String description;
  final String status;
  final String createdAt;

  WalletTransaction({
    required this.id,
    required this.type,
    required this.amount,
    required this.description,
    required this.status,
    required this.createdAt,
  });

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    return WalletTransaction(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      type: json['type'] ?? 'earning',
      amount: (json['amount'] ?? 0).toDouble(),
      description: json['description'] ?? 'Transaction',
      status: json['status'] ?? 'completed',
      createdAt: json['created_at'] ?? '',
    );
  }
}

class ReferralDashboardData {
  final String referralCode;
  final String referralLink;
  final int totalReferred;
  final int registeredCount;
  final int activeCount;
  final int convertedCount;
  final double totalEarnings;
  final double pendingEarnings;
  final double paidEarnings;
  final String currentRuleSummary;

  ReferralDashboardData({
    required this.referralCode,
    required this.referralLink,
    required this.totalReferred,
    required this.registeredCount,
    required this.activeCount,
    required this.convertedCount,
    required this.totalEarnings,
    required this.pendingEarnings,
    required this.paidEarnings,
    required this.currentRuleSummary,
  });

  factory ReferralDashboardData.fromJson(Map<String, dynamic> json) {
    return ReferralDashboardData(
      referralCode: json['referral_code'] ?? '',
      referralLink: json['referral_link'] ?? '',
      totalReferred: json['total_referred'] ?? 0,
      registeredCount: json['registered_count'] ?? 0,
      activeCount: json['active_count'] ?? 0,
      convertedCount: json['converted_count'] ?? 0,
      totalEarnings: (json['total_earnings'] ?? 0).toDouble(),
      pendingEarnings: (json['pending_earnings'] ?? 0).toDouble(),
      paidEarnings: (json['paid_earnings'] ?? 0).toDouble(),
      currentRuleSummary: json['current_rule_summary'] ?? 'Standard Referral Rewards Active',
    );
  }
}

class TeamMember {
  final int id;
  final String name;
  final String joinedDate;
  final String status;
  final int qualifyingConversions;
  final double referralEarnings;

  TeamMember({
    required this.id,
    required this.name,
    required this.joinedDate,
    required this.status,
    required this.qualifyingConversions,
    required this.referralEarnings,
  });

  factory TeamMember.fromJson(Map<String, dynamic> json) {
    return TeamMember(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] ?? 'User',
      joinedDate: json['joined_date'] ?? json['created_at'] ?? '',
      status: json['status'] ?? 'active',
      qualifyingConversions: json['qualifying_conversions'] ?? 0,
      referralEarnings: (json['referral_earnings'] ?? 0).toDouble(),
    );
  }
}
