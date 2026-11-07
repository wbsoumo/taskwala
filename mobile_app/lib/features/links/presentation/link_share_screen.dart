import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:dio/dio.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/models/models.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/campaign_logo.dart';

class LinkShareScreen extends ConsumerStatefulWidget {
  final AffiliateLink link;

  const LinkShareScreen({
    super.key,
    required this.link,
  });

  @override
  ConsumerState<LinkShareScreen> createState() => _LinkShareScreenState();
}

class _LinkShareScreenState extends ConsumerState<LinkShareScreen> {
  // Category tabs: special_deal, friends_share, cashback_alert, custom
  String _selectedCategory = 'special_deal';

  CampaignCollateral? _selectedCollateral;
  File? _customMediaFile;
  bool _isDownloadingMedia = false;
  late TextEditingController _messageController;

  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _messageController = TextEditingController();

    // Default select first image collateral if available
    final availableCollaterals = widget.link.collaterals.isNotEmpty
        ? widget.link.collaterals
        : (widget.link.campaign?.collaterals ?? const []);

    if (availableCollaterals.isNotEmpty) {
      _selectedCollateral = availableCollaterals.firstWhere(
        (c) => c.mediaType == 'image',
        orElse: () => availableCollaterals.first,
      );
    }

    _applyTemplateForCategory(_selectedCategory);
  }

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  List<CampaignCollateral> get _allCollaterals {
    if (widget.link.collaterals.isNotEmpty) {
      return widget.link.collaterals;
    }
    return widget.link.campaign?.collaterals ?? const [];
  }

  void _applyTemplateForCategory(String categoryKey) {
    final link = widget.link;
    final campaignName = link.campaignName;
    final payoutStr = link.customerPayout > 0 ? '₹${link.customerPayout.toStringAsFixed(0)}' : 'exclusive rewards';
    final url = link.secureUrl;

    // Check if admin uploaded a collateral matching this category preset
    final matchingAdminCollaterals = _allCollaterals.where((c) {
      return c.categoryPreset == categoryKey && (c.defaultCaption?.isNotEmpty ?? false);
    }).toList();

    String templateText = '';

    if (matchingAdminCollaterals.isNotEmpty && matchingAdminCollaterals.first.defaultCaption != null) {
      final base = matchingAdminCollaterals.first.defaultCaption!.trim();
      templateText = '$base\n\n👉 Claim Now: $url';
      if (_selectedCollateral == null && matchingAdminCollaterals.first.mediaUrl.isNotEmpty) {
        _selectedCollateral = matchingAdminCollaterals.first;
      }
    } else {
      switch (categoryKey) {
        case 'special_deal':
          templateText = '🔥 *LOOT DEAL ALERT!* Don\'t miss out on *$campaignName*!\n\n'
              '💰 Get flat $payoutStr instant cashback / benefit when you sign up today.\n\n'
              '⚡ 100% verified & genuine offer.\n'
              '👉 *Tap here to claim:* $url\n\n'
              '⏳ Limited period loot deal!';
          break;

        case 'friends_share':
          templateText = '👋 Hey! Found an awesome genuine deal on *$campaignName*.\n\n'
              '✨ I just activated it and you can also get $payoutStr directly!\n\n'
              '👉 Sign up using my link: $url\n\n'
              'Let me know once done if you need any help!';
          break;

        case 'cashback_alert':
          templateText = '⚡ *INSTANT CASHBACK ALERT!* 💸\n\n'
              'Get direct $payoutStr reward on *$campaignName*!\n'
              '✅ Easy registration\n'
              '✅ Fast payout directly to UPI\n\n'
              '👉 *Click to start earning:* $url';
          break;

        case 'custom':
          templateText = 'Check out *$campaignName* to get $payoutStr!\n\n👉 $url';
          break;
      }
    }

    setState(() {
      _selectedCategory = categoryKey;
      _messageController.text = templateText;
    });
  }

  Future<void> _pickCustomMedia() async {
    try {
      final picked = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 85,
      );
      if (picked != null) {
        setState(() {
          _customMediaFile = File(picked.path);
          _selectedCollateral = null;
        });
        HapticFeedback.lightImpact();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not pick image: $e'), backgroundColor: AppColors.danger),
        );
      }
    }
  }

  Future<void> _shareOnWhatsApp() async {
    HapticFeedback.lightImpact();
    final message = _messageController.text.trim();

    // Check if we have media to share with caption
    if (_customMediaFile != null && await _customMediaFile!.exists()) {
      await Share.shareXFiles(
        [XFile(_customMediaFile!.path)],
        text: message,
      );
      return;
    }

    if (_selectedCollateral != null && _selectedCollateral!.mediaUrl.isNotEmpty) {
      await _shareCollateralFile(preferWhatsApp: true);
      return;
    }

    // Direct WhatsApp text intent
    final encoded = Uri.encodeComponent(message);
    final whatsappUri = Uri.parse('whatsapp://send?text=$encoded');
    final webWhatsappUri = Uri.parse('https://api.whatsapp.com/send?text=$encoded');

    try {
      if (await canLaunchUrl(whatsappUri)) {
        await launchUrl(whatsappUri, mode: LaunchMode.externalApplication);
      } else if (await canLaunchUrl(webWhatsappUri)) {
        await launchUrl(webWhatsappUri, mode: LaunchMode.externalApplication);
      } else {
        await Share.share(message);
      }
    } catch (_) {
      await Share.share(message);
    }
  }

  Future<void> _shareToOtherApps() async {
    HapticFeedback.lightImpact();
    final message = _messageController.text.trim();

    if (_customMediaFile != null && await _customMediaFile!.exists()) {
      await Share.shareXFiles(
        [XFile(_customMediaFile!.path)],
        text: message,
      );
      return;
    }

    if (_selectedCollateral != null && _selectedCollateral!.mediaUrl.isNotEmpty) {
      await _shareCollateralFile(preferWhatsApp: false);
      return;
    }

    await Share.share(message);
  }

  Future<void> _shareCollateralFile({required bool preferWhatsApp}) async {
    final message = _messageController.text.trim();
    final collateral = _selectedCollateral;
    if (collateral == null || collateral.mediaUrl.isEmpty) {
      if (preferWhatsApp) {
        _shareOnWhatsApp();
      } else {
        Share.share(message);
      }
      return;
    }

    setState(() => _isDownloadingMedia = true);
    try {
      final dio = Dio();
      final tempDir = await getTemporaryDirectory();
      final isVideo = collateral.mediaType == 'video';
      final ext = isVideo ? 'mp4' : 'jpg';
      final savePath = '${tempDir.path}/taskwala_share_${collateral.id}_${DateTime.now().millisecondsSinceEpoch}.$ext';

      await dio.download(collateral.mediaUrl, savePath);

      if (!mounted) return;
      setState(() => _isDownloadingMedia = false);

      await Share.shareXFiles(
        [XFile(savePath)],
        text: message,
      );
    } catch (e) {
      if (mounted) {
        setState(() => _isDownloadingMedia = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Poster sharing fallback to link: $e'),
            backgroundColor: AppColors.danger,
          ),
        );
        Share.share(message);
      }
    }
  }

  void _copyMessageToClipboard() {
    Clipboard.setData(ClipboardData(text: _messageController.text));
    HapticFeedback.lightImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: AppColors.primary,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        content: Row(
          children: [
            const Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
            const SizedBox(width: 8),
            Text(
              'Promotional message & link copied!',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final link = widget.link;
    final catColors = AppColors.getCategoryColors(link.category);
    final collaterals = _allCollaterals;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 19, color: AppColors.textPrimary),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Promotional Share Studio',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 16,
                color: AppColors.textPrimary,
              ),
            ),
            Text(
              'Pick a poster & message template to boost conversions',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 14),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: link.isActive ? AppColors.mintBg : AppColors.surfaceSecondary,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: link.isActive ? AppColors.mint.withValues(alpha: 0.3) : AppColors.border,
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 7,
                  height: 7,
                  decoration: BoxDecoration(
                    color: link.isActive ? AppColors.mint : AppColors.textMuted,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 5),
                Text(
                  link.isActive ? 'Active' : 'Paused',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: link.isActive ? AppColors.mint : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      body: Stack(
        children: [
          ListView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 160),
            children: [
              // 1. Campaign & Live Link Info Card
              _buildCampaignSummaryCard(link, catColors),

              const SizedBox(height: 18),

              // 2. Marketing Collaterals / Posters Selector
              _buildCollateralSelector(collaterals),

              const SizedBox(height: 20),

              // 3. Message Template Categories
              _buildCategorySelector(),

              const SizedBox(height: 14),

              // 4. Message Editor & Formatting
              _buildMessageEditor(),

              const SizedBox(height: 20),

              // 5. WhatsApp Live Message Preview
              _buildLivePreviewCard(link),
            ],
          ),

          // Sticky Bottom Action Bar
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: _buildBottomActionBar(),
          ),
        ],
      ),
    );
  }

  Widget _buildCampaignSummaryCard(AffiliateLink link, ({Color fg, Color bg}) catColors) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.soft,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CampaignLogo(
                logoUrl: link.campaignLogo,
                name: link.campaignName,
                category: link.category,
                size: 48,
                borderRadius: 14,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      link.campaignName,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2.5),
                          decoration: BoxDecoration(
                            color: catColors.bg,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            link.category,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: catColors.fg,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          'You earn ₹${link.affiliateCommission.toStringAsFixed(0)}',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: AppColors.mint,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          // Link bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.background,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.borderSubtle),
            ),
            child: Row(
              children: [
                const Icon(Icons.link_rounded, size: 18, color: AppColors.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    link.secureUrl,
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                BouncingCard(
                  onTap: () {
                    Clipboard.setData(ClipboardData(text: link.secureUrl));
                    HapticFeedback.lightImpact();
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Link copied!'),
                        duration: Duration(seconds: 1),
                        behavior: SnackBarBehavior.floating,
                      ),
                    );
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: AppColors.primaryLight,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Copy',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCollateralSelector(List<CampaignCollateral> collaterals) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                const Icon(Icons.perm_media_rounded, size: 17, color: AppColors.primary),
                const SizedBox(width: 6),
                Text(
                  '1. Select Poster / Media',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            Text(
              '${collaterals.length} available',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 115,
          child: ListView(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            children: [
              // Option 0: No Media (Text Only)
              _buildCollateralChoiceCard(
                title: 'Text Only',
                subtitle: 'No poster',
                icon: Icons.notes_rounded,
                isSelected: _selectedCollateral == null && _customMediaFile == null,
                onTap: () {
                  setState(() {
                    _selectedCollateral = null;
                    _customMediaFile = null;
                  });
                },
              ),

              // Option: Pick Custom from Device Gallery
              _buildCustomUploadChoiceCard(),

              // Admin Collaterals
              ...collaterals.map((c) {
                final isSelected = _selectedCollateral?.id == c.id && _customMediaFile == null;
                return _buildAdminCollateralCard(c, isSelected);
              }),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildCollateralChoiceCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return BouncingCard(
      onTap: onTap,
      child: Container(
        width: 95,
        margin: const EdgeInsets.only(right: 10),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primaryLight : AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 28,
              color: isSelected ? AppColors.primary : AppColors.textSecondary,
            ),
            const SizedBox(height: 6),
            Text(
              title,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                color: isSelected ? AppColors.primary : AppColors.textPrimary,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
            ),
            Text(
              subtitle,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 9,
                color: AppColors.textSecondary,
              ),
              textAlign: TextAlign.center,
              maxLines: 1,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCustomUploadChoiceCard() {
    final hasCustom = _customMediaFile != null;
    return BouncingCard(
      onTap: _pickCustomMedia,
      child: Container(
        width: 95,
        margin: const EdgeInsets.only(right: 10),
        decoration: BoxDecoration(
          color: hasCustom ? AppColors.primaryLight : AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: hasCustom ? AppColors.primary : AppColors.border,
            width: hasCustom ? 2 : 1,
          ),
        ),
        clipBehavior: Clip.antiAlias,
        child: hasCustom
            ? Stack(
                fit: StackFit.expand,
                children: [
                  Image.file(_customMediaFile!, fit: BoxFit.cover),
                  Positioned(
                    top: 4,
                    right: 4,
                    child: Container(
                      padding: const EdgeInsets.all(3),
                      decoration: const BoxDecoration(
                        color: AppColors.primary,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.check, size: 10, color: Colors.white),
                    ),
                  ),
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    child: Container(
                      color: Colors.black.withValues(alpha: 0.6),
                      padding: const EdgeInsets.symmetric(vertical: 2),
                      child: Text(
                        'Custom Image',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 9,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ],
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.add_photo_alternate_rounded, size: 26, color: AppColors.primary),
                  const SizedBox(height: 6),
                  Text(
                    '+ My Image',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                  Text(
                    'From gallery',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 9,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildAdminCollateralCard(CampaignCollateral collateral, bool isSelected) {
    final isVideo = collateral.mediaType == 'video';
    final isText = collateral.mediaType == 'text';

    return BouncingCard(
      onTap: () {
        setState(() {
          _selectedCollateral = collateral;
          _customMediaFile = null;
        });
        if (collateral.defaultCaption?.isNotEmpty ?? false) {
          final url = widget.link.secureUrl;
          _messageController.text = '${collateral.defaultCaption!.trim()}\n\n👉 $url';
        }
      },
      child: Container(
        width: 100,
        margin: const EdgeInsets.only(right: 10),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 2.2 : 1,
          ),
          boxShadow: isSelected ? [BoxShadow(color: AppColors.primary.withValues(alpha: 0.25), blurRadius: 6)] : null,
        ),
        clipBehavior: Clip.antiAlias,
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (isText)
              Container(
                color: AppColors.surfaceSecondary,
                padding: const EdgeInsets.all(8),
                alignment: Alignment.center,
                child: const Icon(Icons.short_text_rounded, size: 28, color: AppColors.primary),
              )
            else if (isVideo)
              Container(
                color: Colors.black87,
                alignment: Alignment.center,
                child: const Icon(Icons.play_circle_fill_rounded, size: 30, color: Colors.white),
              )
            else
              Image.network(
                collateral.thumbnailUrl ?? collateral.mediaUrl,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  color: AppColors.surfaceSecondary,
                  child: const Icon(Icons.image_rounded, color: AppColors.textMuted),
                ),
              ),

            // Top check badge if selected
            if (isSelected)
              Positioned(
                top: 5,
                right: 5,
                child: Container(
                  padding: const EdgeInsets.all(3),
                  decoration: const BoxDecoration(
                    color: AppColors.primary,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.check, size: 10, color: Colors.white),
                ),
              ),

            // Bottom Title
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Container(
                color: Colors.black.withValues(alpha: 0.65),
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 3),
                child: Text(
                  collateral.title,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCategorySelector() {
    final categories = [
      {'key': 'special_deal', 'label': 'Special Deal', 'icon': '🔥'},
      {'key': 'friends_share', 'label': 'Friends Share', 'icon': '👋'},
      {'key': 'cashback_alert', 'label': 'Cashback Alert', 'icon': '⚡'},
      {'key': 'custom', 'label': 'Custom Text', 'icon': '✍️'},
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.auto_awesome_rounded, size: 17, color: AppColors.primary),
            const SizedBox(width: 6),
            Text(
              '2. Promotional Message Template',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 14,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          physics: const BouncingScrollPhysics(),
          child: Row(
            children: categories.map((cat) {
              final isSelected = _selectedCategory == cat['key'];
              return Padding(
                padding: const EdgeInsets.only(right: 8),
                child: BouncingCard(
                  onTap: () => _applyTemplateForCategory(cat['key']!),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: isSelected ? AppColors.primary : AppColors.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: isSelected ? AppColors.primary : AppColors.border,
                      ),
                      boxShadow: isSelected ? [BoxShadow(color: AppColors.primary.withValues(alpha: 0.3), blurRadius: 6)] : null,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(cat['icon']!, style: const TextStyle(fontSize: 13)),
                        const SizedBox(width: 6),
                        Text(
                          cat['label']!,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                            color: isSelected ? Colors.white : AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildMessageEditor() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Customize Message Copy',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textSecondary,
                ),
              ),
              Row(
                children: [
                  BouncingCard(
                    onTap: () {
                      final url = widget.link.secureUrl;
                      final current = _messageController.text;
                      if (!current.contains(url)) {
                        _messageController.text = '$current\n\n👉 $url';
                      }
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.add_link_rounded, size: 14, color: AppColors.primary),
                          const SizedBox(width: 4),
                          Text(
                            '+ Link',
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
                  const SizedBox(width: 8),
                  BouncingCard(
                    onTap: () => _applyTemplateForCategory(_selectedCategory),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.refresh_rounded, size: 14, color: AppColors.textSecondary),
                          const SizedBox(width: 4),
                          Text(
                            'Reset',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _messageController,
            maxLines: 5,
            minLines: 3,
            onChanged: (_) => setState(() {}),
            style: GoogleFonts.plusJakartaSans(
              fontSize: 13,
              height: 1.4,
              color: AppColors.textPrimary,
            ),
            decoration: InputDecoration(
              isDense: true,
              hintText: 'Type your promotional message here...',
              hintStyle: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textMuted),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.borderSubtle),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.borderSubtle),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
              ),
              filled: true,
              fillColor: AppColors.background,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLivePreviewCard(AffiliateLink link) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFE5DDD5).withValues(alpha: 0.6), // WhatsApp chat background vibe
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.visibility_rounded, size: 16, color: Color(0xFF075E54)),
                  const SizedBox(width: 6),
                  Text(
                    'Live WhatsApp Preview',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: const Color(0xFF075E54),
                    ),
                  ),
                ],
              ),
              BouncingCard(
                onTap: _copyMessageToClipboard,
                child: Row(
                  children: [
                    const Icon(Icons.copy_rounded, size: 13, color: Color(0xFF075E54)),
                    const SizedBox(width: 4),
                    Text(
                      'Copy Text',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: const Color(0xFF075E54),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          // WhatsApp Bubble
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(14),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Media preview inside bubble
                if (_customMediaFile != null)
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(
                      _customMediaFile!,
                      height: 160,
                      width: double.infinity,
                      fit: BoxFit.cover,
                    ),
                  )
                else if (_selectedCollateral != null && _selectedCollateral!.mediaUrl.isNotEmpty && _selectedCollateral!.mediaType != 'text')
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.network(
                      _selectedCollateral!.thumbnailUrl ?? _selectedCollateral!.mediaUrl,
                      height: 160,
                      width: double.infinity,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        height: 100,
                        color: Colors.grey.shade200,
                        alignment: Alignment.center,
                        child: const Icon(Icons.image, color: Colors.grey),
                      ),
                    ),
                  ),

                if (_customMediaFile != null || (_selectedCollateral != null && _selectedCollateral!.mediaType != 'text'))
                  const SizedBox(height: 8),

                // Caption text
                Text(
                  _messageController.text,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 13,
                    height: 1.45,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomActionBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        border: const Border(top: BorderSide(color: AppColors.border)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              // WhatsApp Primary Share Button
              Expanded(
                flex: 3,
                child: BouncingCard(
                  onTap: _isDownloadingMedia ? null : _shareOnWhatsApp,
                  child: Container(
                    height: 48,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF25D366), Color(0xFF128C7E)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF25D366).withValues(alpha: 0.35),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    alignment: Alignment.center,
                    child: _isDownloadingMedia
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                          )
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.chat_bubble_rounded, color: Colors.white, size: 20),
                              const SizedBox(width: 8),
                              Text(
                                'Share on WhatsApp',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w800,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                  ),
                ),
              ),

              const SizedBox(width: 10),

              // Other Apps Button
              Expanded(
                flex: 2,
                child: BouncingCard(
                  onTap: _isDownloadingMedia ? null : _shareToOtherApps,
                  child: Container(
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppColors.surfaceSecondary,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppColors.border),
                    ),
                    alignment: Alignment.center,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.share_rounded, size: 18, color: AppColors.textPrimary),
                        const SizedBox(width: 6),
                        Text(
                          'More',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
