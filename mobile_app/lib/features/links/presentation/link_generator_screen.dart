import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:dio/dio.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';

import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/models/models.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/category_badge.dart';
import '../../../shared/widgets/campaign_logo.dart';

class LinkGeneratorScreen extends ConsumerStatefulWidget {
  final Campaign campaign;

  const LinkGeneratorScreen({super.key, required this.campaign});

  @override
  ConsumerState<LinkGeneratorScreen> createState() => _LinkGeneratorScreenState();
}

class _LinkGeneratorScreenState extends ConsumerState<LinkGeneratorScreen> {
  late TextEditingController _customerPayoutController;
  bool _isGenerating = false;
  AffiliateLink? _generatedLink;
  String? _error;
  bool _isCopied = false;

  // Marketing Collaterals & WhatsApp Posters state
  CampaignCollateral? _selectedCollateral;
  File? _userUploadedMediaFile;
  String? _userUploadedMediaType; // 'image' or 'video'
  int _selectedTemplateIndex = 0;
  bool _isCustomCaption = false;
  late TextEditingController _customCaptionController;
  bool _isSharingWithMedia = false;
  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    // Default to 50% split for high conversion
    final defaultSplit = (widget.campaign.affiliatePayout * 0.5).round();
    _customerPayoutController = TextEditingController(text: defaultSplit.toString());
    _customCaptionController = TextEditingController();

    if (widget.campaign.collaterals.isNotEmpty) {
      _selectedCollateral = widget.campaign.collaterals.first;
    }
  }

  @override
  void dispose() {
    _customerPayoutController.dispose();
    _customCaptionController.dispose();
    super.dispose();
  }

  double get _customerPayout =>
      (double.tryParse(_customerPayoutController.text) ?? 0.0)
          .clamp(0.0, widget.campaign.affiliatePayout);

  double get _affiliateCommission =>
      (widget.campaign.affiliatePayout - _customerPayout)
          .clamp(0.0, widget.campaign.affiliatePayout);

  double get _customerPercent => widget.campaign.affiliatePayout > 0
      ? (_customerPayout / widget.campaign.affiliatePayout).clamp(0.0, 1.0)
      : 0.0;

  Future<AffiliateLink?> _generateLink() async {
    setState(() {
      _isGenerating = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final campaignIdentifier = widget.campaign.publicId.isNotEmpty
          ? widget.campaign.publicId
          : (widget.campaign.id != 0 ? widget.campaign.id.toString() : '');

      final response = await api.post('/links/generate', data: {
        'campaign_public_id': campaignIdentifier,
        'campaign_id': campaignIdentifier,
        'customer_payout': _customerPayout,
      });

      final resData = response.data;
      final isSuccess = response.statusCode == 200 ||
          response.statusCode == 201 ||
          (resData is Map && resData['success'] == true);

      if (isSuccess && resData is Map) {
        Map<String, dynamic> linkData = {};
        if (resData['data'] is Map) {
          linkData = Map<String, dynamic>.from(resData['data']);
        } else if (resData['link'] is Map) {
          linkData = Map<String, dynamic>.from(resData['link']);
        } else {
          linkData = Map<String, dynamic>.from(resData);
        }

        if (resData['public_url'] != null &&
            (linkData['public_url'] == null || linkData['public_url'].toString().isEmpty)) {
          linkData['public_url'] = resData['public_url'];
        }

        if (linkData['campaign_name'] == null || linkData['campaign_name'].toString().isEmpty) {
          linkData['campaign_name'] = widget.campaign.name;
        }

        if (linkData['campaign_logo'] == null && widget.campaign.logo != null) {
          linkData['campaign_logo'] = widget.campaign.logo;
        }

        final newLink = AffiliateLink.fromJson(linkData);
        setState(() {
          _generatedLink = newLink;
          _isGenerating = false;
        });
        ref.invalidate(myLinksProvider);
        return newLink;
      } else {
        final msg = resData is Map ? resData['message']?.toString() : null;
        setState(() {
          _error = msg ?? 'Failed to generate link. Please try again.';
          _isGenerating = false;
        });
        return null;
      }
    } on DioException catch (e) {
      String msg = 'Failed to generate link';
      if (e.response?.data is Map) {
        final map = e.response!.data as Map;
        msg = map['message']?.toString() ??
            (map['errors'] is Map ? (map['errors'] as Map).values.first?.toString() : null) ??
            msg;
      } else if (e.message != null && e.message!.isNotEmpty) {
        msg = e.message!;
      }
      setState(() {
        _error = msg;
        _isGenerating = false;
      });
      return null;
    } catch (e) {
      setState(() {
        _error = 'Error connecting to backend server: $e';
        _isGenerating = false;
      });
      return null;
    }
  }

  Future<void> _shareWithCollateralOrGenerate({bool preferWhatsApp = true}) async {
    AffiliateLink? link = _generatedLink;
    if (link == null) {
      link = await _generateLink();
      if (link == null) return;
    }
    await _shareWithCollateral(link.secureUrl, preferWhatsApp: preferWhatsApp);
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    HapticFeedback.mediumImpact();
    setState(() => _isCopied = true);
    Future.delayed(const Duration(seconds: 2), () {
      if (mounted) setState(() => _isCopied = false);
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: const Color(0xFF0F172A),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        content: Row(
          children: [
            const Icon(Icons.check_circle_rounded, color: AppColors.mint, size: 20),
            const SizedBox(width: 10),
            Text(
              'Link copied to clipboard!',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                color: Colors.white,
                fontSize: 13,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _shareViaWhatsApp(String url) async {
    final message = Uri.encodeComponent(
      '🔥 Hey! Check out this special offer on Taskwala for ${widget.campaign.name}.\n'
      '🎁 Get ₹${_customerPayout.toStringAsFixed(0)} instant cashback/reward!\n'
      '👉 Tap here to get started: $url',
    );
    final whatsappUri = Uri.parse('whatsapp://send?text=$message');
    final webWhatsappUri = Uri.parse('https://api.whatsapp.com/send?text=$message');

    try {
      final launched = await launchUrl(whatsappUri, mode: LaunchMode.externalApplication);
      if (!launched) {
        await launchUrl(webWhatsappUri, mode: LaunchMode.externalApplication);
      }
    } catch (_) {
      Share.share(
        '🔥 Special offer for ${widget.campaign.name} on Taskwala!\nGet ₹${_customerPayout.toStringAsFixed(0)} reward!\n👉 $url',
      );
    }
  }

  List<String> _getTemplates(String url) {
    final reward = _customerPayout > 0 ? '₹${_customerPayout.toStringAsFixed(0)} instant cashback' : 'exclusive rewards';
    return [
      '🔥 Special Offer on ${widget.campaign.name}!\n🎁 Get $reward when you register today.\n👉 Tap here to claim now: $url',
      '👋 Hey friends! Check out ${widget.campaign.name} on Taskwala.\n💰 You get $reward directly on completion!\n🚀 Start here: $url',
      '⚡ Verified Cashback Alert!\nEarn $reward with ${widget.campaign.name}.\n100% verified task via Taskwala App:\n👉 $url',
    ];
  }

  String _getFinalMessage(String url) {
    if (_isCustomCaption && _customCaptionController.text.trim().isNotEmpty) {
      return '${_customCaptionController.text.trim()}\n\n👉 $url';
    }
    final templates = _getTemplates(url);
    if (_selectedTemplateIndex < templates.length) {
      return templates[_selectedTemplateIndex];
    }
    return templates[0];
  }

  Future<void> _pickUserMedia(bool isVideo) async {
    try {
      final XFile? file = isVideo
          ? await _picker.pickVideo(source: ImageSource.gallery)
          : await _picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
      if (file != null) {
        setState(() {
          _userUploadedMediaFile = File(file.path);
          _userUploadedMediaType = isVideo ? 'video' : 'image';
          _selectedCollateral = null; // Prioritize custom file
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to pick media: $e'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  Future<void> _shareWithCollateral(String url, {bool preferWhatsApp = true}) async {
    final message = _getFinalMessage(url);

    // Case 1: Custom user-picked image or video
    if (_userUploadedMediaFile != null && await _userUploadedMediaFile!.exists()) {
      setState(() => _isSharingWithMedia = true);
      HapticFeedback.lightImpact();
      try {
        await Share.shareXFiles(
          [XFile(_userUploadedMediaFile!.path)],
          text: message,
        );
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Sharing failed: $e'), backgroundColor: AppColors.danger),
          );
        }
      } finally {
        if (mounted) setState(() => _isSharingWithMedia = false);
      }
      return;
    }

    // Case 2: Selected campaign collateral (image or video) uploaded by admin
    if (_selectedCollateral != null && _selectedCollateral!.mediaUrl.isNotEmpty) {
      setState(() => _isSharingWithMedia = true);
      HapticFeedback.lightImpact();
      try {
        final dio = Dio();
        final tempDir = await getTemporaryDirectory();
        final isVideo = _selectedCollateral!.mediaType == 'video';
        final ext = isVideo ? 'mp4' : 'jpg';
        final savePath = '${tempDir.path}/taskwala_poster_${_selectedCollateral!.id}.$ext';

        final file = File(savePath);
        if (!await file.exists()) {
          await dio.download(_selectedCollateral!.mediaUrl, savePath);
        }

        if (!mounted) return;
        setState(() => _isSharingWithMedia = false);

        await Share.shareXFiles(
          [XFile(savePath)],
          text: message,
        );
      } catch (e) {
        if (mounted) {
          setState(() => _isSharingWithMedia = false);
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Could not download poster, sharing text link instead: $e'),
              backgroundColor: AppColors.danger,
            ),
          );
          if (preferWhatsApp) {
            _shareViaWhatsApp(url);
          } else {
            Share.share(message);
          }
        }
      }
      return;
    }

    // Case 3: Link and text only
    if (preferWhatsApp) {
      _shareViaWhatsApp(url);
    } else {
      Share.share(message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final catColors = AppColors.getCategoryColors(widget.campaign.category);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Generate Offer Link',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: AppColors.textPrimary,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(18, 16, 18, 40),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // HERO SHOWCASE CARD WITH CAMPAIGN LOGO
            Container(
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppColors.border),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.06),
                    blurRadius: 18,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Column(
                children: [
                  // Top Accent Gradient Strip
                  Container(
                    height: 5,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          catColors.fg,
                          AppColors.primary,
                          AppColors.mint,
                        ],
                      ),
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(18),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Campaign Logo
                        CampaignLogo(
                          logoUrl: widget.campaign.logo,
                          name: widget.campaign.name,
                          category: widget.campaign.category,
                          size: 56,
                          borderRadius: 18,
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  CategoryBadge(label: widget.campaign.category, fontSize: 10),
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                    decoration: BoxDecoration(
                                      color: AppColors.mintBg,
                                      borderRadius: BorderRadius.circular(6),
                                      border: Border.all(
                                        color: AppColors.mint.withValues(alpha: 0.25),
                                        width: 0.8,
                                      ),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        const Icon(Icons.verified_rounded, size: 11, color: AppColors.mint),
                                        const SizedBox(width: 3),
                                        Text(
                                          'Verified Offer',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 9,
                                            fontWeight: FontWeight.w700,
                                            color: AppColors.mint,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 5),
                              Text(
                                widget.campaign.name,
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.textPrimary,
                                  height: 1.2,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Text(
                                    'Max Reward: ',
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 12,
                                      color: AppColors.textSecondary,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  Text(
                                    '₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 14,
                                      fontWeight: FontWeight.w800,
                                      color: AppColors.mint,
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                                    decoration: BoxDecoration(
                                      color: AppColors.surfaceSecondary,
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    child: Text(
                                      widget.campaign.conversionType,
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 9,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ).animate().fadeIn(duration: 350.ms).slideY(begin: -0.05, end: 0),

            const SizedBox(height: 20),

            if (_generatedLink == null) ...[
              // COMMISSION SPLIT STUDIO
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: AppColors.border),
                  boxShadow: AppShadows.soft,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Commission Split Studio',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Incentivize leads with cashback to boost conversions',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 11,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: AppColors.primaryLight,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            '${(_customerPercent * 100).toInt()}% Shared',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),

                    // DUAL COMPARATIVE CARDS (Customer vs You)
                    Row(
                      children: [
                        // Customer Payout Card (Mint)
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [
                                  AppColors.mintBg,
                                  AppColors.mintBg.withValues(alpha: 0.4),
                                ],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                color: AppColors.mint.withValues(alpha: 0.35),
                                width: 1.2,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(6),
                                      decoration: BoxDecoration(
                                        color: AppColors.mint.withValues(alpha: 0.2),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(
                                        Icons.card_giftcard_rounded,
                                        size: 15,
                                        color: AppColors.mint,
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      'Customer Gets',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.mint,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  '₹${_customerPayout.toStringAsFixed(0)}',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 22,
                                    fontWeight: FontWeight.w900,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'Direct Cashback',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 10,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        // Affiliate Profit Card (Royal Indigo)
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [
                                  AppColors.primaryLight,
                                  AppColors.primaryLight.withValues(alpha: 0.4),
                                ],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                color: AppColors.primary.withValues(alpha: 0.35),
                                width: 1.2,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(6),
                                      decoration: BoxDecoration(
                                        color: AppColors.primary.withValues(alpha: 0.2),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(
                                        Icons.account_balance_wallet_rounded,
                                        size: 15,
                                        color: AppColors.primary,
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      'Your Profit',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.primary,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  '₹${_affiliateCommission.toStringAsFixed(0)}',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 22,
                                    fontWeight: FontWeight.w900,
                                    color: AppColors.primary,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'In Your Wallet',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 10,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 18),

                    // Visual Split Proportion Bar
                    ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: Container(
                        height: 8,
                        decoration: const BoxDecoration(
                          color: AppColors.primary,
                        ),
                        child: Row(
                          children: [
                            AnimatedContainer(
                              duration: const Duration(milliseconds: 150),
                              width: (MediaQuery.of(context).size.width - 76) * _customerPercent,
                              color: AppColors.mint,
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Quick Percentage Preset Pills
                    Row(
                      children: [
                        {'label': '0% (Keep All)', 'pct': 0.0},
                        {'label': '25%', 'pct': 0.25},
                        {'label': '50% (Best)', 'pct': 0.50},
                        {'label': '75%', 'pct': 0.75},
                      ].map((item) {
                        final pct = item['pct'] as double;
                        final label = item['label'] as String;
                        final amt = (widget.campaign.affiliatePayout * pct).round();
                        final isSelected = _customerPayout.round() == amt;

                        return Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 3.0),
                            child: BouncingCard(
                              onTap: () {
                                HapticFeedback.selectionClick();
                                setState(() {
                                  _customerPayoutController.text = amt.toString();
                                });
                              },
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 180),
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                decoration: BoxDecoration(
                                  color: isSelected ? AppColors.primary : AppColors.surfaceSecondary,
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isSelected ? AppColors.primary : AppColors.border,
                                    width: 1.2,
                                  ),
                                  boxShadow: isSelected
                                      ? [
                                          BoxShadow(
                                            color: AppColors.primary.withValues(alpha: 0.25),
                                            blurRadius: 8,
                                            offset: const Offset(0, 3),
                                          ),
                                        ]
                                      : null,
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  label,
                                  textAlign: TextAlign.center,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 10,
                                    fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                    color: isSelected ? Colors.white : AppColors.textPrimary,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),

                    const SizedBox(height: 20),

                    // Custom Amount Field
                    TextField(
                      controller: _customerPayoutController,
                      keyboardType: TextInputType.number,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textPrimary,
                      ),
                      decoration: InputDecoration(
                        labelText: 'Custom Customer Reward (₹)',
                        labelStyle: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textSecondary,
                        ),
                        prefixIcon: const Icon(Icons.currency_rupee_rounded, size: 20, color: AppColors.mint),
                        suffixText: '/ ₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                        suffixStyle: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                          color: AppColors.textMuted,
                          fontSize: 13,
                        ),
                        filled: true,
                        fillColor: AppColors.surfaceSecondary,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                          borderSide: const BorderSide(color: AppColors.border),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                        ),
                      ),
                      onChanged: (_) => setState(() {}),
                    ),

                    const SizedBox(height: 12),

                    // Slider
                    SliderTheme(
                      data: SliderTheme.of(context).copyWith(
                        activeTrackColor: AppColors.mint,
                        inactiveTrackColor: AppColors.surfaceSecondary,
                        thumbColor: AppColors.mint,
                        overlayColor: AppColors.mint.withValues(alpha: 0.15),
                        trackHeight: 6,
                        thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 10),
                      ),
                      child: Slider(
                        value: _customerPayout.clamp(
                          0.0,
                          widget.campaign.affiliatePayout > 0 ? widget.campaign.affiliatePayout : 1.0,
                        ),
                        min: 0.0,
                        max: widget.campaign.affiliatePayout > 0 ? widget.campaign.affiliatePayout : 1.0,
                        onChanged: (val) {
                          setState(() {
                            _customerPayoutController.text = val.round().toString();
                          });
                        },
                      ),
                    ),

                    const SizedBox(height: 14),

                    // Pro Conversion Tip
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.borderSubtle),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.lightbulb_rounded, color: Colors.amber, size: 18),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Offering ₹${((widget.campaign.affiliatePayout * 0.5)).toStringAsFixed(0)} cashback to leads can boost conversion rates by 3x!',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ).animate().fadeIn(delay: 100.ms, duration: 400.ms),
            ] else ...[
              // HIGH-CONVERTING LINK GENERATED SUCCESS STATE
              Container(
                padding: const EdgeInsets.all(22),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(26),
                  border: Border.all(color: AppColors.mint.withValues(alpha: 0.4), width: 1.5),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.mint.withValues(alpha: 0.12),
                      blurRadius: 24,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    // Celebration Header
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.mintBg,
                        shape: BoxShape.circle,
                        border: Border.all(color: AppColors.mint.withValues(alpha: 0.3), width: 1.5),
                      ),
                      child: const Icon(Icons.check_circle_rounded, color: AppColors.mint, size: 48),
                    ),
                    const SizedBox(height: 14),
                    Text(
                      'Your Offer Link is Live!',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Anyone clicking this link will be tracked to your wallet.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary),
                    ),

                    const SizedBox(height: 18),

                    // Chosen Split Breakdown Box
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.borderSubtle),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          Column(
                            children: [
                              Text(
                                'Customer Cashback',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 10,
                                  color: AppColors.textSecondary,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '₹${_customerPayout.toStringAsFixed(0)}',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.mint,
                                ),
                              ),
                            ],
                          ),
                          Container(width: 1, height: 28, color: AppColors.border),
                          Column(
                            children: [
                              Text(
                                'Your Net Profit',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 10,
                                  color: AppColors.textSecondary,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '₹${_affiliateCommission.toStringAsFixed(0)}',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.primary,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 18),

                    // Copy Link Input Box
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border, width: 1.2),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.link_rounded, size: 20, color: AppColors.primary),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              _generatedLink!.secureUrl,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: AppColors.textPrimary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 8),
                          BouncingCard(
                            onTap: () => _copyToClipboard(_generatedLink!.secureUrl),
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 200),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: _isCopied ? AppColors.mint : AppColors.primaryLight,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    _isCopied ? Icons.check_rounded : Icons.copy_rounded,
                                    size: 15,
                                    color: _isCopied ? Colors.white : AppColors.primary,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    _isCopied ? 'Copied' : 'Copy',
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w800,
                                      color: _isCopied ? Colors.white : AppColors.primary,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ).animate().fadeIn(duration: 400.ms).scale(begin: const Offset(0.96, 0.96)),
            ],

            const SizedBox(height: 20),

            // MARKETING COLLATERALS & WHATSAPP POSTERS SECTION (ALWAYS VISIBLE)
            Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.photo_library_rounded, size: 20, color: AppColors.primary),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Text(
                                          'Marketing Collateral',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 14,
                                            color: AppColors.textPrimary,
                                          ),
                                        ),
                                        const SizedBox(width: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF10B981).withValues(alpha: 0.12),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            '+350% Clicks',
                                            style: GoogleFonts.plusJakartaSans(
                                              fontSize: 9.5,
                                              fontWeight: FontWeight.w800,
                                              color: const Color(0xFF059669),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    Text(
                                      'Pick a poster or video to send along with your link',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 11,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 14),

                          // Horizontal Collateral Selector Strip
                          SizedBox(
                            height: 120,
                            child: ListView(
                              scrollDirection: Axis.horizontal,
                              children: [
                                // Option 0: Link Only / No Poster
                                InkWell(
                                  onTap: () {
                                    setState(() {
                                      _selectedCollateral = null;
                                      _userUploadedMediaFile = null;
                                    });
                                  },
                                  borderRadius: BorderRadius.circular(14),
                                  child: Container(
                                    width: 90,
                                    margin: const EdgeInsets.only(right: 10),
                                    decoration: BoxDecoration(
                                      color: (_selectedCollateral == null && _userUploadedMediaFile == null)
                                          ? AppColors.primary.withValues(alpha: 0.08)
                                          : AppColors.surface,
                                      borderRadius: BorderRadius.circular(14),
                                      border: Border.all(
                                        color: (_selectedCollateral == null && _userUploadedMediaFile == null)
                                            ? AppColors.primary
                                            : AppColors.border,
                                        width: (_selectedCollateral == null && _userUploadedMediaFile == null) ? 2 : 1,
                                      ),
                                    ),
                                    child: Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(
                                          Icons.link_off_rounded,
                                          color: (_selectedCollateral == null && _userUploadedMediaFile == null)
                                              ? AppColors.primary
                                              : AppColors.textMuted,
                                          size: 28,
                                        ),
                                        const SizedBox(height: 6),
                                        Text(
                                          'Link Only\n(No Media)',
                                          textAlign: TextAlign.center,
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 10.5,
                                            fontWeight: FontWeight.w700,
                                            color: (_selectedCollateral == null && _userUploadedMediaFile == null)
                                                ? AppColors.primary
                                                : AppColors.textSecondary,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),

                                // Campaign Collaterals uploaded by Admin
                                ...widget.campaign.collaterals.map((collateral) {
                                  final isSelected = _selectedCollateral?.id == collateral.id && _userUploadedMediaFile == null;
                                  final isVideo = collateral.mediaType == 'video';

                                  return InkWell(
                                    onTap: () {
                                      setState(() {
                                        _selectedCollateral = collateral;
                                        _userUploadedMediaFile = null;
                                      });
                                    },
                                    borderRadius: BorderRadius.circular(14),
                                    child: Container(
                                      width: 100,
                                      margin: const EdgeInsets.only(right: 10),
                                      decoration: BoxDecoration(
                                        color: isSelected ? AppColors.primary.withValues(alpha: 0.08) : AppColors.surface,
                                        borderRadius: BorderRadius.circular(14),
                                        border: Border.all(
                                          color: isSelected ? AppColors.primary : AppColors.border,
                                          width: isSelected ? 2.2 : 1,
                                        ),
                                      ),
                                      child: Stack(
                                        children: [
                                          Column(
                                            crossAxisAlignment: CrossAxisAlignment.stretch,
                                            children: [
                                              Expanded(
                                                child: ClipRRect(
                                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                                                  child: Image.network(
                                                    collateral.thumbnailUrl ?? collateral.mediaUrl,
                                                    fit: BoxFit.cover,
                                                    errorBuilder: (_, __, ___) => Container(
                                                      color: AppColors.surfaceSecondary,
                                                      child: Icon(
                                                        isVideo ? Icons.videocam_rounded : Icons.image_rounded,
                                                        color: AppColors.textMuted,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                              ),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                                color: AppColors.surface,
                                                child: Text(
                                                  collateral.title,
                                                  maxLines: 1,
                                                  overflow: TextOverflow.ellipsis,
                                                  textAlign: TextAlign.center,
                                                  style: GoogleFonts.plusJakartaSans(
                                                    fontSize: 10,
                                                    fontWeight: FontWeight.w700,
                                                    color: AppColors.textPrimary,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                          Positioned(
                                            top: 4,
                                            left: 4,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: Colors.black.withValues(alpha: 0.7),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Icon(
                                                    isVideo ? Icons.play_arrow_rounded : Icons.photo_size_select_actual_rounded,
                                                    size: 10,
                                                    color: Colors.white,
                                                  ),
                                                  const SizedBox(width: 2),
                                                  Text(
                                                    isVideo ? 'VIDEO' : 'POSTER',
                                                    style: GoogleFonts.plusJakartaSans(
                                                      fontSize: 8,
                                                      fontWeight: FontWeight.w800,
                                                      color: Colors.white,
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),
                                          if (isSelected)
                                            Positioned(
                                              top: 4,
                                              right: 4,
                                              child: Container(
                                                padding: const EdgeInsets.all(2),
                                                decoration: const BoxDecoration(
                                                  color: AppColors.primary,
                                                  shape: BoxShape.circle,
                                                ),
                                                child: const Icon(Icons.check, size: 12, color: Colors.white),
                                              ),
                                            ),
                                        ],
                                      ),
                                    ),
                                  );
                                }),

                                // Option: Upload / Pick from Device
                                InkWell(
                                  onTap: () {
                                    showModalBottomSheet(
                                      context: context,
                                      backgroundColor: AppColors.surface,
                                      shape: const RoundedRectangleBorder(
                                        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
                                      ),
                                      builder: (ctx) => SafeArea(
                                        child: Padding(
                                          padding: const EdgeInsets.all(16),
                                          child: Column(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              Text(
                                                'Upload Marketing Media',
                                                style: GoogleFonts.plusJakartaSans(
                                                  fontWeight: FontWeight.w800,
                                                  fontSize: 15,
                                                ),
                                              ),
                                              const SizedBox(height: 12),
                                              ListTile(
                                                leading: const Icon(Icons.image_rounded, color: AppColors.primary),
                                                title: const Text('Pick Image / Poster from Gallery'),
                                                onTap: () {
                                                  Navigator.pop(ctx);
                                                  _pickUserMedia(false);
                                                },
                                              ),
                                              ListTile(
                                                leading: const Icon(Icons.videocam_rounded, color: Colors.purple),
                                                title: const Text('Pick Promo Video from Gallery'),
                                                onTap: () {
                                                  Navigator.pop(ctx);
                                                  _pickUserMedia(true);
                                                },
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    );
                                  },
                                  borderRadius: BorderRadius.circular(14),
                                  child: Container(
                                    width: 100,
                                    margin: const EdgeInsets.only(right: 10),
                                    decoration: BoxDecoration(
                                      color: (_userUploadedMediaFile != null)
                                          ? AppColors.primary.withValues(alpha: 0.08)
                                          : AppColors.surface,
                                      borderRadius: BorderRadius.circular(14),
                                      border: Border.all(
                                        color: (_userUploadedMediaFile != null)
                                            ? AppColors.primary
                                            : AppColors.border,
                                        width: (_userUploadedMediaFile != null) ? 2 : 1,
                                      ),
                                    ),
                                    child: (_userUploadedMediaFile != null)
                                        ? Stack(
                                            children: [
                                              ClipRRect(
                                                borderRadius: BorderRadius.circular(12),
                                                child: _userUploadedMediaType == 'image'
                                                    ? Image.file(
                                                        _userUploadedMediaFile!,
                                                        width: 100,
                                                        height: 120,
                                                        fit: BoxFit.cover,
                                                      )
                                                    : Container(
                                                        color: Colors.black87,
                                                        child: const Center(
                                                          child: Icon(Icons.videocam_rounded, color: Colors.white, size: 32),
                                                        ),
                                                      ),
                                              ),
                                              Positioned(
                                                top: 4,
                                                right: 4,
                                                child: Container(
                                                  padding: const EdgeInsets.all(2),
                                                  decoration: const BoxDecoration(
                                                    color: AppColors.primary,
                                                    shape: BoxShape.circle,
                                                  ),
                                                  child: const Icon(Icons.check, size: 12, color: Colors.white),
                                                ),
                                              ),
                                              Positioned(
                                                bottom: 0,
                                                left: 0,
                                                right: 0,
                                                child: Container(
                                                  color: Colors.black54,
                                                  padding: const EdgeInsets.symmetric(vertical: 2),
                                                  child: Text(
                                                    'Custom',
                                                    textAlign: TextAlign.center,
                                                    style: GoogleFonts.plusJakartaSans(
                                                      color: Colors.white,
                                                      fontSize: 9,
                                                      fontWeight: FontWeight.w700,
                                                    ),
                                                  ),
                                                ),
                                              ),
                                            ],
                                          )
                                        : Column(
                                            mainAxisAlignment: MainAxisAlignment.center,
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.all(8),
                                                decoration: BoxDecoration(
                                                  color: AppColors.primary.withValues(alpha: 0.1),
                                                  shape: BoxShape.circle,
                                                ),
                                                child: const Icon(Icons.add_photo_alternate_rounded,
                                                    color: AppColors.primary, size: 20),
                                              ),
                                              const SizedBox(height: 6),
                                              Text(
                                                '+ Upload\nPoster/Video',
                                                textAlign: TextAlign.center,
                                                style: GoogleFonts.plusJakartaSans(
                                                  fontSize: 10,
                                                  fontWeight: FontWeight.w700,
                                                  color: AppColors.primary,
                                                ),
                                              ),
                                            ],
                                          ),
                                  ),
                                ),
                              ],
                            ),
                          ),

                          const SizedBox(height: 16),
                          const Divider(height: 1),
                          const SizedBox(height: 14),

                          // PROMOTIONAL CAPTION TEMPLATES
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Promotional Message Copy',
                                style: GoogleFonts.plusJakartaSans(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              InkWell(
                                onTap: () => setState(() => _isCustomCaption = !_isCustomCaption),
                                child: Text(
                                  _isCustomCaption ? 'Use Templates' : 'Write Custom',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 11.5,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),

                          if (!_isCustomCaption) ...[
                            // Template Chips
                            Wrap(
                              spacing: 8,
                              runSpacing: 6,
                              children: [
                                ChoiceChip(
                                  label: const Text('🔥 Special Deal'),
                                  selected: _selectedTemplateIndex == 0,
                                  onSelected: (s) => setState(() => _selectedTemplateIndex = 0),
                                  selectedColor: AppColors.primary.withValues(alpha: 0.15),
                                  labelStyle: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: _selectedTemplateIndex == 0 ? AppColors.primary : AppColors.textSecondary,
                                  ),
                                ),
                                ChoiceChip(
                                  label: const Text('👋 Friends Share'),
                                  selected: _selectedTemplateIndex == 1,
                                  onSelected: (s) => setState(() => _selectedTemplateIndex = 1),
                                  selectedColor: AppColors.primary.withValues(alpha: 0.15),
                                  labelStyle: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: _selectedTemplateIndex == 1 ? AppColors.primary : AppColors.textSecondary,
                                  ),
                                ),
                                ChoiceChip(
                                  label: const Text('⚡ Cashback Alert'),
                                  selected: _selectedTemplateIndex == 2,
                                  onSelected: (s) => setState(() => _selectedTemplateIndex = 2),
                                  selectedColor: AppColors.primary.withValues(alpha: 0.15),
                                  labelStyle: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: _selectedTemplateIndex == 2 ? AppColors.primary : AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),

                            // Preview Box
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: AppColors.surface,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.border),
                              ),
                              child: Text(
                                _getFinalMessage(_generatedLink?.secureUrl ?? 'https://taskwala.co.in/go/your-link'),
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 12,
                                  color: AppColors.textSecondary,
                                  height: 1.4,
                                ),
                              ),
                            ),
                          ] else ...[
                            TextFormField(
                              controller: _customCaptionController,
                              maxLines: 3,
                              decoration: InputDecoration(
                                hintText: 'Type your promotional message here... (link is automatically appended at the bottom)',
                                hintStyle: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textMuted),
                                filled: true,
                                fillColor: AppColors.surface,
                                contentPadding: const EdgeInsets.all(12),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: const BorderSide(color: AppColors.border),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: const BorderSide(color: AppColors.border),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                                ),
                              ),
                              onChanged: (_) => setState(() {}),
                            ),
                          ],
                        ],
                      ),
            ),

            const SizedBox(height: 20),

            if (_error != null) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.dangerBg,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.danger.withValues(alpha: 0.25)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline_rounded, size: 18, color: AppColors.danger),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _error!,
                        style: GoogleFonts.plusJakartaSans(
                          color: AppColors.danger,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],

            if (_generatedLink == null) ...[
              // PRE-GENERATION ACTIONS:
              // WhatsApp Share Button with Poster (Calls _shareWithCollateralOrGenerate)
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton.icon(
                  onPressed: (_isSharingWithMedia || _isGenerating)
                      ? null
                      : () => _shareWithCollateralOrGenerate(preferWhatsApp: true),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    elevation: 3,
                    shadowColor: const Color(0xFF25D366).withValues(alpha: 0.4),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  icon: (_isSharingWithMedia || _isGenerating)
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.send_rounded, size: 18),
                  label: Text(
                    _isSharingWithMedia
                        ? 'Preparing Media...'
                        : _isGenerating
                            ? 'Creating Link...'
                            : (_selectedCollateral != null || _userUploadedMediaFile != null)
                                ? 'Share on WhatsApp (with Poster)'
                                : 'Share on WhatsApp',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: (_isSharingWithMedia || _isGenerating)
                          ? null
                          : () => _shareWithCollateralOrGenerate(preferWhatsApp: false),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.textPrimary,
                        side: const BorderSide(color: AppColors.border, width: 1.2),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      icon: const Icon(Icons.share_outlined, size: 17),
                      label: Text(
                        'Other Apps',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: (_isSharingWithMedia || _isGenerating) ? null : _generateLink,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      icon: const Icon(Icons.auto_awesome_rounded, size: 17),
                      label: Text(
                        'Create Link Only',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ] else ...[
              // POST-GENERATION ACTIONS:
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton.icon(
                  onPressed: _isSharingWithMedia
                      ? null
                      : () => _shareWithCollateral(_generatedLink!.secureUrl, preferWhatsApp: true),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    elevation: 3,
                    shadowColor: const Color(0xFF25D366).withValues(alpha: 0.4),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  icon: _isSharingWithMedia
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.send_rounded, size: 18),
                  label: Text(
                    _isSharingWithMedia
                        ? 'Preparing Media...'
                        : (_selectedCollateral != null || _userUploadedMediaFile != null)
                            ? 'Share on WhatsApp (with Poster)'
                            : 'Share on WhatsApp',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 15,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _isSharingWithMedia
                          ? null
                          : () => _shareWithCollateral(_generatedLink!.secureUrl, preferWhatsApp: false),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.textPrimary,
                        side: const BorderSide(color: AppColors.border, width: 1.2),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      icon: const Icon(Icons.share_outlined, size: 17),
                      label: Text(
                        'Other Apps',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        final uri = Uri.parse(_generatedLink!.secureUrl);
                        await launchUrl(uri, mode: LaunchMode.externalApplication);
                      },
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.textPrimary,
                        side: const BorderSide(color: AppColors.border, width: 1.2),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                        padding: const EdgeInsets.symmetric(vertical: 13),
                      ),
                      icon: const Icon(Icons.open_in_new_rounded, size: 17),
                      label: Text(
                        'Test Link',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),

              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  TextButton(
                    onPressed: () => context.go('/links'),
                    child: Text(
                      'View in My Links →',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w800,
                        fontSize: 13,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text('•', style: TextStyle(color: AppColors.textMuted)),
                  const SizedBox(width: 8),
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _generatedLink = null;
                      });
                    },
                    child: Text(
                      'Customize Again',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
