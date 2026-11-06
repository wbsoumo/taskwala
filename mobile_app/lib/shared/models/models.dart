int _parseInt(dynamic val) {
  if (val == null) return 0;
  if (val is int) return val;
  if (val is num) return val.toInt();
  return int.tryParse(val.toString()) ?? 0;
}

double _parseDouble(dynamic val) {
  if (val == null) return 0.0;
  if (val is double) return val;
  if (val is num) return val.toDouble();
  return double.tryParse(val.toString()) ?? 0.0;
}

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
      publicId: json['public_id']?.toString() ?? json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      mobile: (json['mobile_number'] ?? json['mobile'])?.toString(),
      referralCode: json['referral_code']?.toString(),
      status: json['status']?.toString() ?? 'active',
      kycStatus: json['kyc_status']?.toString() ?? 'approved',
      payoutEnabled: json['payout_enabled'] == true || json['payout_enabled'] == 1 || json['payout_enabled'] == '1',
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

class CampaignCollateral {
  final int id;
  final String title;
  final String mediaType; // 'image' or 'video'
  final String mediaUrl;
  final String? thumbnailUrl;
  final String? defaultCaption;

  CampaignCollateral({
    required this.id,
    required this.title,
    required this.mediaType,
    required this.mediaUrl,
    this.thumbnailUrl,
    this.defaultCaption,
  });

  factory CampaignCollateral.fromJson(Map<String, dynamic> json) {
    return CampaignCollateral(
      id: _parseInt(json['id']),
      title: json['title']?.toString() ?? 'Marketing Asset',
      mediaType: json['media_type']?.toString() ?? 'image',
      mediaUrl: json['media_url']?.toString() ?? '',
      thumbnailUrl: json['thumbnail_url']?.toString(),
      defaultCaption: json['default_caption']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'media_type': mediaType,
        'media_url': mediaUrl,
        'thumbnail_url': thumbnailUrl,
        'default_caption': defaultCaption,
      };
}

class Campaign {
  final int id;
  final String publicId;
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
  final bool checkDuplicateIp;
  final List<CampaignCollateral> collaterals;

  Campaign({
    required this.id,
    this.publicId = '',
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
    this.checkDuplicateIp = false,
    this.collaterals = const [],
  });

  factory Campaign.fromJson(Map<String, dynamic> json) {
    final parsedId = _parseInt(json['id']);
    final pubId = json['public_id']?.toString() ?? (parsedId != 0 ? parsedId.toString() : '');
    final fallbackId = parsedId != 0 ? parsedId : pubId.hashCode.abs();

    final rawCollaterals = json['collaterals'];
    final List<CampaignCollateral> collateralList = (rawCollaterals is List)
        ? rawCollaterals
            .map((c) => CampaignCollateral.fromJson(Map<String, dynamic>.from(c as Map)))
            .toList()
        : const [];

    return Campaign(
      id: fallbackId,
      publicId: pubId,
      name: json['name']?.toString() ?? '',
      logo: (json['logo'] ?? json['logo_url'])?.toString(),
      category: json['category']?.toString() ?? 'General',
      advertiserPayout: _parseDouble(json['advertiser_payout'] ?? json['payout_amount']),
      affiliatePayout: _parseDouble(json['allocated_affiliate_payout'] ?? json['affiliate_payout'] ?? json['user_payout'] ?? json['payout_amount']),
      conversionType: json['conversion_type']?.toString() ?? 'CPA',
      status: json['status']?.toString() ?? 'active',
      description: json['short_description']?.toString() ?? json['description']?.toString(),
      terms: json['terms']?.toString(),
      eligibility: json['eligibility']?.toString(),
      checkDuplicateIp: json['check_duplicate_ip'] == true || json['check_duplicate_ip'] == 1,
      collaterals: collateralList,
    );
  }
}

class AffiliateLink {
  final int id;
  final int campaignId;
  final String campaignName;
  final String? campaignLogo;
  final String category;
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
    this.campaignLogo,
    this.category = 'General',
    required this.secureUrl,
    required this.code,
    required this.customerPayout,
    required this.affiliateCommission,
    required this.clicks,
    required this.conversions,
    required this.createdAt,
  });

  factory AffiliateLink.fromJson(Map<String, dynamic> json) {
    final parsedId = _parseInt(json['id']);
    final parsedCampId = _parseInt(json['campaign_id'] ?? json['campaign']?['id']);
    final token = json['secure_token']?.toString() ?? json['code']?.toString() ?? '';
    final rawUrl = json['public_url']?.toString() ?? json['secure_url']?.toString() ?? json['tracking_url']?.toString();
    final url = (rawUrl != null && rawUrl.isNotEmpty)
        ? rawUrl
        : (token.isNotEmpty ? 'https://taskwala.co.in/go/$token' : '');

    return AffiliateLink(
      id: parsedId != 0 ? parsedId : (json['public_id']?.toString().hashCode.abs() ?? 0),
      campaignId: parsedCampId != 0 ? parsedCampId : 1,
      campaignName: json['campaign_name']?.toString() ?? json['campaign']?['name']?.toString() ?? 'Campaign',
      campaignLogo: (json['campaign_logo'] ?? json['campaign']?['logo'] ?? json['campaign']?['logo_url'])?.toString(),
      category: json['category']?.toString() ?? json['campaign']?['category']?.toString() ?? 'General',
      secureUrl: url,
      code: token,
      customerPayout: _parseDouble(json['customer_payout']),
      affiliateCommission: _parseDouble(json['affiliate_commission']),
      clicks: _parseInt(json['clicks_count'] ?? json['click_count'] ?? json['clicks']),
      conversions: _parseInt(json['conversions_count'] ?? json['conversion_count'] ?? json['conversions']),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}

class ClickReport {
  final int id;
  final String campaignName;
  final String? campaignLogo;
  final String category;
  final String linkCode;
  final String ipAddress;
  final String status;
  final bool isConverted;
  final double affiliateCommission;
  final String clickedAt;

  ClickReport({
    required this.id,
    required this.campaignName,
    this.campaignLogo,
    this.category = 'General',
    required this.linkCode,
    required this.ipAddress,
    this.status = 'valid',
    this.isConverted = false,
    this.affiliateCommission = 0.0,
    required this.clickedAt,
  });

  factory ClickReport.fromJson(Map<String, dynamic> json) {
    final isConv = json['conversion'] != null || json['is_converted'] == true || json['is_converted'] == 1;
    return ClickReport(
      id: _parseInt(json['id']),
      campaignName: json['campaign_name']?.toString() ?? json['campaign']?['name']?.toString() ?? 'Campaign',
      campaignLogo: (json['campaign_logo'] ?? json['campaign']?['logo'] ?? json['campaign']?['logo_url'])?.toString(),
      category: json['category']?.toString() ?? json['campaign']?['category']?.toString() ?? 'General',
      linkCode: json['link_code']?.toString() ?? json['link']?['code']?.toString() ?? json['link']?['secure_token']?.toString() ?? json['click_id']?.toString() ?? 'N/A',
      ipAddress: json['ip_address']?.toString() ?? 'Masked',
      status: json['status']?.toString() ?? (isConv ? 'converted' : 'valid'),
      isConverted: isConv,
      affiliateCommission: _parseDouble(json['affiliate_commission'] ?? json['commission']),
      clickedAt: json['created_at']?.toString() ?? json['clicked_at']?.toString() ?? '',
    );
  }
}

class ConversionReport {
  final int id;
  final String campaignName;
  final String? campaignLogo;
  final String category;
  final String referenceId;
  final double customerPayout;
  final double commission;
  final String status;
  final String payoutStatus;
  final String createdAt;

  ConversionReport({
    required this.id,
    required this.campaignName,
    this.campaignLogo,
    this.category = 'General',
    required this.referenceId,
    required this.customerPayout,
    required this.commission,
    required this.status,
    required this.payoutStatus,
    required this.createdAt,
  });

  factory ConversionReport.fromJson(Map<String, dynamic> json) {
    final parsedId = _parseInt(json['id']);
    return ConversionReport(
      id: parsedId,
      campaignName: json['campaign_name']?.toString() ?? json['campaign']?['name']?.toString() ?? 'Campaign',
      campaignLogo: (json['campaign_logo'] ?? json['campaign']?['logo'] ?? json['campaign']?['logo_url'])?.toString(),
      category: json['category']?.toString() ?? json['campaign']?['category']?.toString() ?? 'General',
      referenceId: json['reference_id']?.toString() ?? json['click_id']?.toString() ?? json['public_id']?.toString() ?? 'REF-$parsedId',
      customerPayout: _parseDouble(json['customer_payout'] ?? json['customerPayout']?['amount']),
      commission: _parseDouble(json['commission'] ?? json['affiliate_commission'] ?? json['payoutSnapshot']?['affiliate_payout']),
      status: json['status']?.toString() ?? 'pending',
      payoutStatus: json['payout_status']?.toString() ?? 'unpaid',
      createdAt: json['conversion_time']?.toString() ?? json['created_at']?.toString() ?? '',
    );
  }
}

class PayoutRequestItem {
  final int id;
  final String publicId;
  final double amount;
  final String upiId;
  final String? upiHolderName;
  final String status;
  final String? transactionId;
  final String? adminNotes;
  final String createdAt;
  final String? processedAt;

  PayoutRequestItem({
    required this.id,
    required this.publicId,
    required this.amount,
    required this.upiId,
    this.upiHolderName,
    required this.status,
    this.transactionId,
    this.adminNotes,
    required this.createdAt,
    this.processedAt,
  });

  factory PayoutRequestItem.fromJson(Map<String, dynamic> json) {
    return PayoutRequestItem(
      id: _parseInt(json['id']),
      publicId: json['public_id']?.toString() ?? '',
      amount: _parseDouble(json['amount']),
      upiId: json['upi_id']?.toString() ?? '',
      upiHolderName: json['upi_holder_name']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      transactionId: json['transaction_id']?.toString(),
      adminNotes: json['admin_notes']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
      processedAt: json['processed_at']?.toString(),
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
  final double minWithdrawalThreshold;
  final List<WalletTransaction> transactions;
  final List<PayoutRequestItem> payoutRequests;

  WalletData({
    required this.availableBalance,
    required this.pendingBalance,
    required this.approvedBalance,
    required this.totalEarnings,
    required this.totalPaid,
    this.upiId,
    required this.upiStatus,
    this.minWithdrawalThreshold = 500.0,
    required this.transactions,
    this.payoutRequests = const [],
  });

  factory WalletData.fromJson(Map<String, dynamic> json) {
    final rawTx = json['transactions'];
    final List<dynamic> txData = rawTx is List
        ? rawTx
        : (rawTx is Map && rawTx['data'] is List ? rawTx['data'] : []);

    final txList = txData.map((t) => WalletTransaction.fromJson(Map<String, dynamic>.from(t as Map))).toList();

    final rawPayouts = json['payout_requests'];
    final List<dynamic> payoutData = rawPayouts is List
        ? rawPayouts
        : (rawPayouts is Map && rawPayouts['data'] is List ? rawPayouts['data'] : []);

    final payoutList = payoutData.map((p) => PayoutRequestItem.fromJson(Map<String, dynamic>.from(p as Map))).toList();

    final threshold = _parseDouble(json['min_withdrawal_threshold']);

    return WalletData(
      availableBalance: _parseDouble(json['available_balance'] ?? json['balance']),
      pendingBalance: _parseDouble(json['pending_balance']),
      approvedBalance: _parseDouble(json['approved_balance']),
      totalEarnings: _parseDouble(json['total_earnings']),
      totalPaid: _parseDouble(json['total_paid']),
      upiId: json['upi_id']?.toString(),
      upiStatus: json['upi_status']?.toString() ?? 'not_added',
      minWithdrawalThreshold: threshold > 0 ? threshold : 500.0,
      transactions: txList,
      payoutRequests: payoutList,
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
      id: _parseInt(json['id']),
      type: json['type']?.toString() ?? 'earning',
      amount: _parseDouble(json['amount']),
      description: json['description']?.toString() ?? 'Transaction',
      status: json['status']?.toString() ?? 'completed',
      createdAt: json['created_at']?.toString() ?? '',
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
    final stats = json['stats'] is Map ? json['stats'] : json;
    return ReferralDashboardData(
      referralCode: json['referral_code']?.toString() ?? '',
      referralLink: json['referral_url']?.toString() ?? json['referral_link']?.toString() ?? '',
      totalReferred: _parseInt(stats['total_referred']),
      registeredCount: _parseInt(stats['active_referred'] ?? stats['registered_count']),
      activeCount: _parseInt(stats['active_referred'] ?? stats['active_count']),
      convertedCount: _parseInt(stats['converted_referred'] ?? stats['converted_count']),
      totalEarnings: _parseDouble(stats['total_referral_earnings'] ?? stats['total_earnings']),
      pendingEarnings: _parseDouble(stats['pending_referral_earnings'] ?? stats['pending_earnings']),
      paidEarnings: _parseDouble(stats['paid_referral_earnings'] ?? stats['paid_earnings']),
      currentRuleSummary: json['current_rule']?['summary']?.toString() ?? json['current_rule_summary']?.toString() ?? 'Standard Referral Rewards Active',
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
      id: _parseInt(json['id']),
      name: json['name']?.toString() ?? 'User',
      joinedDate: json['joined_date']?.toString() ?? json['created_at']?.toString() ?? '',
      status: json['status']?.toString() ?? 'active',
      qualifyingConversions: _parseInt(json['qualifying_conversions']),
      referralEarnings: _parseDouble(json['referral_earnings']),
    );
  }
}

class MissingConversionTicket {
  final int id;
  final String publicId;
  final int campaignId;
  final String campaignName;
  final String? campaignLogo;
  final String category;
  final String? clickId;
  final String? orderReferenceId;
  final String taskDate;
  final String screenshotUrl;
  final String? notes;
  final String status;
  final String? adminNotes;
  final double approvedPayout;
  final String createdAt;
  final String? resolvedAt;

  MissingConversionTicket({
    required this.id,
    required this.publicId,
    required this.campaignId,
    required this.campaignName,
    this.campaignLogo,
    this.category = 'General',
    this.clickId,
    this.orderReferenceId,
    required this.taskDate,
    required this.screenshotUrl,
    this.notes,
    required this.status,
    this.adminNotes,
    this.approvedPayout = 0.0,
    required this.createdAt,
    this.resolvedAt,
  });

  factory MissingConversionTicket.fromJson(Map<String, dynamic> json) {
    return MissingConversionTicket(
      id: _parseInt(json['id']),
      publicId: json['public_id']?.toString() ?? '',
      campaignId: _parseInt(json['campaign_id'] ?? json['campaign']?['id']),
      campaignName: json['campaign_name']?.toString() ?? json['campaign']?['name']?.toString() ?? 'Campaign',
      campaignLogo: (json['campaign_logo'] ?? json['campaign']?['logo'] ?? json['campaign']?['logo_url'])?.toString(),
      category: json['category']?.toString() ?? json['campaign']?['category']?.toString() ?? 'General',
      clickId: json['click_id']?.toString(),
      orderReferenceId: json['order_reference_id']?.toString(),
      taskDate: json['task_date']?.toString() ?? '',
      screenshotUrl: json['screenshot_url']?.toString() ?? '',
      notes: json['notes']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      adminNotes: json['admin_notes']?.toString(),
      approvedPayout: _parseDouble(json['approved_payout']),
      createdAt: json['created_at']?.toString() ?? '',
      resolvedAt: json['resolved_at']?.toString(),
    );
  }
}

class DealItem {
  final int id;
  final String publicId;
  final String title;
  final String? description;
  final String originalUrl;
  final String? affiliateUrl;
  final String? imageUrl;
  final double? price;
  final double? originalPrice;
  final int? discountPercent;
  final String? commissionRate;
  final String category;
  final String sourceChannel;
  final int clicksCount;
  final String? postedAt;

  DealItem({
    required this.id,
    required this.publicId,
    required this.title,
    this.description,
    required this.originalUrl,
    this.affiliateUrl,
    this.imageUrl,
    this.price,
    this.originalPrice,
    this.discountPercent,
    this.commissionRate,
    this.category = 'General',
    this.sourceChannel = 'Telegram',
    this.clicksCount = 0,
    this.postedAt,
  });

  String get effectiveUrl => (affiliateUrl != null && affiliateUrl!.isNotEmpty)
      ? affiliateUrl!
      : originalUrl;

  int get computedDiscount {
    if (discountPercent != null && discountPercent! > 0) return discountPercent!;
    if (price != null && originalPrice != null && originalPrice! > price!) {
      return (((originalPrice! - price!) / originalPrice!) * 100).round();
    }
    return 0;
  }

  factory DealItem.fromJson(Map<String, dynamic> json) {
    return DealItem(
      id: _parseInt(json['id']),
      publicId: json['public_id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Deal Offer',
      description: json['description']?.toString(),
      originalUrl: json['original_url']?.toString() ?? '',
      affiliateUrl: json['affiliate_url']?.toString(),
      imageUrl: json['image_url']?.toString(),
      price: json['price'] != null ? _parseDouble(json['price']) : null,
      originalPrice: json['original_price'] != null ? _parseDouble(json['original_price']) : null,
      discountPercent: json['discount_percent'] != null ? _parseInt(json['discount_percent']) : null,
      commissionRate: json['commission_rate']?.toString() ?? 'Earn Profit',
      category: json['category']?.toString() ?? 'General',
      sourceChannel: json['source_channel']?.toString() ?? 'Telegram',
      clicksCount: _parseInt(json['clicks_count']),
      postedAt: json['posted_at']?.toString(),
    );
  }
}


