import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';

class ReportsScreen extends ConsumerWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final clicksAsync = ref.watch(clickReportsProvider);
    final conversionsAsync = ref.watch(conversionReportsProvider);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          title: const Text('Performance Reports'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Conversions'),
              Tab(text: 'Click Logs'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            // Conversions Tab
            conversionsAsync.when(
              data: (conversions) {
                if (conversions.isEmpty) {
                  return Center(
                    child: Text('No conversion records found.', style: GoogleFonts.inter(color: AppColors.textMuted)),
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: conversions.length,
                  itemBuilder: (context, index) {
                    final c = conversions[index];
                    final isApproved = c.status.toLowerCase() == 'approved';
                    final isPending = c.status.toLowerCase() == 'pending';

                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(c.campaignName, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 15)),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: (isApproved
                                          ? AppColors.success
                                          : (isPending ? AppColors.warning : AppColors.danger))
                                      .withAlpha(20),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  c.status.toUpperCase(),
                                  style: GoogleFonts.inter(
                                    color: isApproved
                                        ? AppColors.success
                                        : (isPending ? AppColors.warning : AppColors.danger),
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Text('Ref ID: ${c.referenceId}', style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
                          const SizedBox(height: 12),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Commission: ₹${c.commission.toStringAsFixed(0)}',
                                style: GoogleFonts.inter(fontWeight: FontWeight.bold, color: AppColors.primary),
                              ),
                              Text(c.createdAt, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted)),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('Failed to load conversions: $err')),
            ),

            // Click Logs Tab
            clicksAsync.when(
              data: (clicks) {
                if (clicks.isEmpty) {
                  return Center(
                    child: Text('No click logs found.', style: GoogleFonts.inter(color: AppColors.textMuted)),
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: clicks.length,
                  itemBuilder: (context, index) {
                    final click = clicks[index];
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(click.campaignName, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 13)),
                              Text('Link: ${click.linkCode} • IP: ${click.ipAddress}',
                                  style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
                            ],
                          ),
                          Text(click.clickedAt, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted)),
                        ],
                      ),
                    );
                  },
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('Failed to load clicks: $err')),
            ),
          ],
        ),
      ),
    );
  }
}
