import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:dio/dio.dart';

import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/models/models.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/category_badge.dart';

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

  @override
  void initState() {
    super.initState();
    _customerPayoutController = TextEditingController(
      text: (widget.campaign.affiliatePayout * 0.5).toStringAsFixed(0),
    );
  }

  @override
  void dispose() {
    _customerPayoutController.dispose();
    super.dispose();
  }

  double get _customerPayout => double.tryParse(_customerPayoutController.text) ?? 0.0;
  double get _affiliateCommission =>
      (widget.campaign.affiliatePayout - _customerPayout).clamp(0.0, widget.campaign.affiliatePayout);

  void _generateLink() async {
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

        if (resData['public_url'] != null && (linkData['public_url'] == null || linkData['public_url'].toString().isEmpty)) {
          linkData['public_url'] = resData['public_url'];
        }

        if (linkData['campaign_name'] == null || linkData['campaign_name'].toString().isEmpty) {
          linkData['campaign_name'] = widget.campaign.name;
        }

        setState(() {
          _generatedLink = AffiliateLink.fromJson(linkData);
          _isGenerating = false;
        });
        ref.invalidate(myLinksProvider);
      } else {
        final msg = resData is Map ? resData['message']?.toString() : null;
        setState(() {
          _error = msg ?? 'Failed to generate link. Please try again.';
          _isGenerating = false;
        });
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
    } catch (e) {
      setState(() {
        _error = 'Error connecting to backend server: $e';
        _isGenerating = false;
      });
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
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Campaign Banner
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(22),
                border: Border.all(color: AppColors.border),
                boxShadow: AppShadows.soft,
              ),
              child: Row(
                children: [
                  Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      color: catColors.bg,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: catColors.fg.withValues(alpha: 0.2),
                        width: 1,
                      ),
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      widget.campaign.name.isNotEmpty ? widget.campaign.name[0] : 'O',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        color: catColors.fg,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CategoryBadge(label: widget.campaign.category, fontSize: 10),
                        const SizedBox(height: 4),
                        Text(
                          widget.campaign.name,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Text(
                              'Max Payout: ',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                color: AppColors.textSecondary,
                              ),
                            ),
                            Text(
                              '₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
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
            ).animate().fadeIn(duration: 350.ms),

            const SizedBox(height: 22),

            if (_generatedLink == null) ...[
              // Payout Configurator
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(color: AppColors.border),
                  boxShadow: AppShadows.soft,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Commission Sharing',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Choose how much cashback/incentive to share with your customer.',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Quick percentage share pills
                    Row(
                      children: [0.0, 0.25, 0.50, 0.75].map((pct) {
                        final amt = (widget.campaign.affiliatePayout * pct).round();
                        final isSelected = _customerPayout.round() == amt;
                        return Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4.0),
                            child: BouncingCard(
                              onTap: () {
                                setState(() {
                                  _customerPayoutController.text = amt.toString();
                                });
                              },
                              child: Container(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                decoration: BoxDecoration(
                                  color: isSelected ? AppColors.primary : AppColors.surfaceSecondary,
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: isSelected ? AppColors.primary : AppColors.border,
                                  ),
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  '${(pct * 100).toInt()}%',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: isSelected ? Colors.white : AppColors.textPrimary,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        );
                      }).toList(),
                    ),

                    const SizedBox(height: 18),
                    TextField(
                      controller: _customerPayoutController,
                      keyboardType: TextInputType.number,
                      style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.bold),
                      decoration: InputDecoration(
                        labelText: 'Customer Incentive (₹)',
                        prefixIcon: const Icon(Icons.currency_rupee_rounded, size: 20, color: AppColors.mint),
                        helperText: 'Max limit: ₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                      ),
                      onChanged: (_) => setState(() {}),
                    ),
                    const SizedBox(height: 12),

                    // Interactive Slider
                    SliderTheme(
                      data: SliderTheme.of(context).copyWith(
                        activeTrackColor: AppColors.mint,
                        inactiveTrackColor: AppColors.mintBg,
                        thumbColor: AppColors.mint,
                        overlayColor: AppColors.mint.withValues(alpha: 0.15),
                        trackHeight: 4,
                        thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 9),
                      ),
                      child: Slider(
                        value: _customerPayout.clamp(0.0, widget.campaign.affiliatePayout > 0 ? widget.campaign.affiliatePayout : 1.0),
                        min: 0.0,
                        max: widget.campaign.affiliatePayout > 0 ? widget.campaign.affiliatePayout : 1.0,
                        onChanged: (val) {
                          setState(() {
                            _customerPayoutController.text = val.round().toString();
                          });
                        },
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Live Commission Split Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    width: 10,
                                    height: 10,
                                    decoration: const BoxDecoration(color: AppColors.mint, shape: BoxShape.circle),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Customer Receives',
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 13,
                                      color: AppColors.textSecondary,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ],
                              ),
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
                          const Divider(height: 22),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  Container(
                                    width: 10,
                                    height: 10,
                                    decoration: const BoxDecoration(color: AppColors.primary, shape: BoxShape.circle),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Your Net Commission',
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                              Text(
                                '₹${_affiliateCommission.toStringAsFixed(0)}',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w800,
                                  color: AppColors.primary,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 22),

                    if (_error != null) ...[
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                        decoration: BoxDecoration(
                          color: AppColors.dangerBg,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.danger.withValues(alpha: 0.2)),
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
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                    ],

                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: ElevatedButton(
                        onPressed: _isGenerating ? null : _generateLink,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        ),
                        child: _isGenerating
                            ? const SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                              )
                            : Text(
                                'Create Tracking Link',
                                style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700),
                              ),
                      ),
                    ),
                  ],
                ),
              ).animate().fadeIn(delay: 100.ms, duration: 400.ms),
            ] else ...[
              // Generated Success State
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: AppColors.mint.withValues(alpha: 0.3)),
                  boxShadow: AppShadows.soft,
                ),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: const BoxDecoration(
                        color: AppColors.mintBg,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.check_circle_rounded, color: AppColors.mint, size: 52),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Your Link is Ready!',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Share with friends, groups, or social channels to start earning.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 20),

                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              _generatedLink!.secureUrl,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textPrimary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          BouncingCard(
                            onTap: () {
                              Clipboard.setData(ClipboardData(text: _generatedLink!.secureUrl));
                              HapticFeedback.lightImpact();
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  backgroundColor: AppColors.primary,
                                  behavior: SnackBarBehavior.floating,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                  content: Text(
                                    'Link copied to clipboard!',
                                    style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600, color: Colors.white),
                                  ),
                                ),
                              );
                            },
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: AppColors.primaryLight,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: const Icon(Icons.copy_rounded, size: 18, color: AppColors.primary),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 22),

                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Share.share('Check out this offer: ${_generatedLink!.secureUrl}');
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              padding: const EdgeInsets.symmetric(vertical: 14),
                            ),
                            icon: const Icon(Icons.share_rounded, size: 18),
                            label: Text(
                              'Share Link',
                              style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => context.go('/links'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: AppColors.textPrimary,
                              side: const BorderSide(color: AppColors.border),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              padding: const EdgeInsets.symmetric(vertical: 14),
                            ),
                            child: Text(
                              'My Links',
                              style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ).animate().fadeIn(duration: 400.ms).scale(begin: const Offset(0.95, 0.95)),
            ],
          ],
        ),
      ),
    );
  }
}
