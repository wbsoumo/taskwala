import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../shared/models/models.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/skeleton_loader.dart';
import '../../../shared/widgets/campaign_logo.dart';

class MyLinksScreen extends ConsumerStatefulWidget {
  const MyLinksScreen({super.key});

  @override
  ConsumerState<MyLinksScreen> createState() => _MyLinksScreenState();
}

class _MyLinksScreenState extends ConsumerState<MyLinksScreen> {
  String _activeTab = 'Active';
  final Set<int> _togglingIds = {};

  Future<void> _toggleLinkStatus(AffiliateLink link) async {
    if (_togglingIds.contains(link.id)) return;

    setState(() => _togglingIds.add(link.id));
    HapticFeedback.lightImpact();

    try {
      final api = ref.read(apiClientProvider);
      final linkIdentifier = link.publicId.isNotEmpty ? link.publicId : link.id.toString();
      await api.post('/links/$linkIdentifier/toggle-status');
      
      ref.invalidate(myLinksProvider);

      if (mounted) {
        final isNowActive = !link.isActive;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: isNowActive ? AppColors.mint : AppColors.surfaceSecondary,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            content: Row(
              children: [
                Icon(
                  isNowActive ? Icons.check_circle_rounded : Icons.pause_circle_filled_rounded,
                  color: isNowActive ? Colors.white : AppColors.textPrimary,
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(
                  isNowActive ? 'Link activated & tracking clicks!' : 'Link paused. Visits will be blocked.',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w600,
                    color: isNowActive ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        String msg = 'Could not update link status. Please try again.';
        if (e is DioException && e.response?.data is Map && e.response?.data['message'] != null) {
          msg = e.response!.data['message'].toString();
        }
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            content: Text(
              msg,
              style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600, color: Colors.white),
            ),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _togglingIds.remove(link.id));
      }
    }
  }

  List<AffiliateLink> _filterLinks(List<AffiliateLink> all) {
    if (_activeTab == 'Active') {
      return all.where((l) => l.isActive).toList();
    }
    if (_activeTab == 'Paused') {
      return all.where((l) => !l.isActive).toList();
    }
    return all;
  }

  @override
  Widget build(BuildContext context) {
    final linksAsync = ref.watch(myLinksProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'My Tracking Links',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: AppColors.textPrimary,
              ),
            ),
            Text(
              'Share links and monitor clicks & conversions',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          // Filter Tabs (Active, Paused, All)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(
                bottom: BorderSide(color: AppColors.borderSubtle, width: 1),
              ),
            ),
            child: Container(
              height: 44,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.surfaceSecondary,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: ['Active', 'Paused', 'All'].map((tab) {
                  final isSelected = _activeTab == tab;
                  return Expanded(
                    child: BouncingCard(
                      onTap: () => setState(() => _activeTab = tab),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        curve: Curves.easeOutCubic,
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.surface : Colors.transparent,
                          borderRadius: BorderRadius.circular(10),
                          boxShadow: isSelected ? AppShadows.soft : null,
                        ),
                        alignment: Alignment.center,
                        child: Text(
                          tab,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                            color: isSelected ? AppColors.primary : AppColors.textSecondary,
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),

          // Links List
          Expanded(
            child: linksAsync.when(
              data: (links) {
                if (links.isEmpty) {
                  return TaskwalaEmptyState(
                    icon: Icons.link_off_rounded,
                    title: 'No Tracking Links Yet',
                    message: 'Generate your first tracking link from available campaigns to start promoting and earning.',
                    actionLabel: 'Browse Campaigns',
                    onAction: () => context.go('/campaigns'),
                  );
                }

                final filtered = _filterLinks(links);

                if (filtered.isEmpty) {
                  return TaskwalaEmptyState(
                    icon: _activeTab == 'Paused' ? Icons.pause_circle_outline_rounded : Icons.filter_alt_off_rounded,
                    title: _activeTab == 'Paused' ? 'No Paused Links' : 'No Active Links Found',
                    message: _activeTab == 'Paused'
                        ? 'All of your generated tracking links are currently active and live.'
                        : 'You currently have no active links in this filter.',
                    actionLabel: 'View All Links',
                    onAction: () => setState(() => _activeTab = 'All'),
                  );
                }

                return ListView.builder(
                  physics: const BouncingScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                  itemCount: filtered.length,
                  itemBuilder: (context, index) {
                    final link = filtered[index];
                    final isToggling = _togglingIds.contains(link.id);

                    return Container(
                      margin: const EdgeInsets.only(bottom: 14),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: link.isActive ? AppColors.border : AppColors.borderSubtle,
                        ),
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
                                size: 46,
                                borderRadius: 14,
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            link.campaignName,
                                            style: GoogleFonts.plusJakartaSans(
                                              fontSize: 15,
                                              fontWeight: FontWeight.w700,
                                              color: AppColors.textPrimary,
                                            ),
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        const SizedBox(width: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: link.isActive ? AppColors.mintBg : AppColors.surfaceSecondary,
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            link.isActive ? 'ACTIVE' : 'PAUSED',
                                            style: GoogleFonts.plusJakartaSans(
                                              fontSize: 9,
                                              fontWeight: FontWeight.w800,
                                              color: link.isActive ? AppColors.mint : AppColors.textMuted,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 3),
                                    Row(
                                      children: [
                                        Text(
                                          'Customer: ₹${link.customerPayout.toStringAsFixed(0)}',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 11,
                                            color: AppColors.textSecondary,
                                          ),
                                        ),
                                        const SizedBox(width: 6),
                                        Text(
                                          '• You earn ₹${link.affiliateCommission.toStringAsFixed(0)}',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 11,
                                            color: AppColors.mint,
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(width: 8),
                              isToggling
                                  ? const SizedBox(
                                      width: 32,
                                      height: 32,
                                      child: Padding(
                                        padding: EdgeInsets.all(6),
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: AppColors.primary,
                                        ),
                                      ),
                                    )
                                  : Switch(
                                      value: link.isActive,
                                      activeTrackColor: AppColors.primaryLight,
                                      activeThumbColor: AppColors.primary,
                                      inactiveThumbColor: AppColors.textMuted,
                                      onChanged: (_) => _toggleLinkStatus(link),
                                    ),
                            ],
                          ),
                          const SizedBox(height: 14),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            decoration: BoxDecoration(
                              color: AppColors.background,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    link.secureUrl,
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 12,
                                      color: link.isActive ? AppColors.textPrimary : AppColors.textMuted,
                                      fontWeight: FontWeight.w500,
                                    ),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  _buildStatPill('Clicks', link.clicks.toString(), AppColors.sky, AppColors.skyBg),
                                  const SizedBox(width: 8),
                                  _buildStatPill('Conversions', link.conversions.toString(), AppColors.mint, AppColors.mintBg),
                                ],
                              ),
                              Row(
                                children: [
                                  // Promotional Share Studio Button
                                  BouncingCard(
                                    onTap: () {
                                      context.push('/link-share', extra: link);
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                      decoration: BoxDecoration(
                                        color: AppColors.primaryLight,
                                        borderRadius: BorderRadius.circular(10),
                                        border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          const Icon(Icons.share_rounded, size: 16, color: AppColors.primary),
                                          const SizedBox(width: 5),
                                          Text(
                                            'Share',
                                            style: GoogleFonts.plusJakartaSans(
                                              fontSize: 12,
                                              fontWeight: FontWeight.w700,
                                              color: AppColors.primary,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  // Quick Copy Button
                                  BouncingCard(
                                    onTap: () {
                                      Clipboard.setData(ClipboardData(text: link.secureUrl));
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
                                        color: AppColors.mintBg,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: const Icon(Icons.copy_rounded, size: 18, color: AppColors.mint),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ).animate().fadeIn(delay: (index * 40).ms, duration: 320.ms).slideY(begin: 0.06, end: 0);
                  },
                );
              },
              loading: () => ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: 4,
                itemBuilder: (_, __) => const Padding(
                  padding: EdgeInsets.only(bottom: 14),
                  child: SkeletonLoader(height: 140, borderRadius: 20),
                ),
              ),
              error: (err, _) => Center(
                child: Text('Failed to load links: $err', style: GoogleFonts.plusJakartaSans(color: AppColors.danger)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatPill(String label, String value, Color fgColor, Color bgColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: fgColor.withValues(alpha: 0.2),
          width: 0.8,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '$value ',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              color: fgColor,
            ),
          ),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: fgColor,
            ),
          ),
        ],
      ),
    );
  }
}
