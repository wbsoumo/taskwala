import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:go_router/go_router.dart';
import 'package:share_plus/share_plus.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/empty_state.dart';

class MyLinksScreen extends ConsumerStatefulWidget {
  const MyLinksScreen({super.key});

  @override
  ConsumerState<MyLinksScreen> createState() => _MyLinksScreenState();
}

class _MyLinksScreenState extends ConsumerState<MyLinksScreen> {
  String _activeTab = 'Active';

  @override
  Widget build(BuildContext context) {
    final linksAsync = ref.watch(myLinksProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: Text('My Links', style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      body: Column(
        children: [
          // Filter Tabs (Active, Paused, All)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: AppColors.surface,
            child: Container(
              height: 40,
              padding: const EdgeInsets.all(3),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: ['Active', 'Paused', 'All'].map((tab) {
                  final isSelected = _activeTab == tab;
                  return Expanded(
                    child: GestureDetector(
                      onTap: () => setState(() => _activeTab = tab),
                      child: Container(
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.primary : Colors.transparent,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        alignment: Alignment.center,
                        child: Text(
                          tab,
                          style: GoogleFonts.inter(
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                            color: isSelected ? Colors.white : AppColors.textSecondary,
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

                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: links.length,
                  itemBuilder: (context, index) {
                    final link = links[index];
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 40,
                                height: 40,
                                decoration: BoxDecoration(
                                  color: AppColors.primaryLight,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                alignment: Alignment.center,
                                child: Text(
                                  link.campaignName[0],
                                  style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primary),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      link.campaignName,
                                      style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '₹${link.customerPayout.toStringAsFixed(0)} to Customer • ₹${link.affiliateCommission.toStringAsFixed(0)} (You earn)',
                                      style: GoogleFonts.inter(fontSize: 11, color: AppColors.success, fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              ),
                              Switch(
                                value: true,
                                activeColor: AppColors.primary,
                                onChanged: (_) {},
                              ),
                            ],
                          ),
                          const Divider(height: 24),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceAround,
                            children: [
                              _buildMetricItem(link.clicks.toString(), 'Clicks'),
                              Container(width: 1, height: 24, color: AppColors.border),
                              _buildMetricItem(link.conversions.toString(), 'Conversions'),
                              Container(width: 1, height: 24, color: AppColors.border),
                              IconButton(
                                icon: const Icon(Icons.share_outlined, size: 20, color: AppColors.primary),
                                onPressed: () {
                                  Share.share('Check out this link: ${link.secureUrl}');
                                },
                              ),
                              IconButton(
                                icon: const Icon(Icons.copy_outlined, size: 20, color: AppColors.primary),
                                onPressed: () {
                                  Clipboard.setData(ClipboardData(text: link.secureUrl));
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Link copied to clipboard')),
                                  );
                                },
                              ),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('Failed to load links: $err')),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMetricItem(String value, String label) {
    return Column(
      children: [
        Text(value, style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
        Text(label, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
      ],
    );
  }
}
