import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';

class ReferralDashboardScreen extends ConsumerWidget {
  const ReferralDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final referralAsync = ref.watch(referralDashboardProvider);
    final teamAsync = ref.watch(teamMembersProvider);

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          title: const Text('Refer & Earn Program'),
          bottom: const TabBar(
            tabs: [
              Tab(text: 'Overview & Link'),
              Tab(text: 'My Team'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            // Overview Tab
            referralAsync.when(
              data: (refData) => SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Active Rule Info Banner
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.primary.withAlpha(50)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.card_giftcard, color: AppColors.primary, size: 28),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Active Referral Rule',
                                    style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primary)),
                                Text(refData.currentRuleSummary,
                                    style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    // Stats Grid
                    GridView.count(
                      crossAxisCount: 2,
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      childAspectRatio: 1.6,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      children: [
                        _buildStatBox('Total Referred', refData.totalReferred.toString(), Icons.people_outline, AppColors.primary),
                        _buildStatBox('Earnings', '₹${refData.totalEarnings.toStringAsFixed(0)}', Icons.monetization_on_outlined, AppColors.success),
                        _buildStatBox('Pending', '₹${refData.pendingEarnings.toStringAsFixed(0)}', Icons.hourglass_top, AppColors.warning),
                        _buildStatBox('Conversions', refData.convertedCount.toString(), Icons.task_alt, AppColors.secondary),
                      ],
                    ),

                    const SizedBox(height: 24),

                    // Referral Link Card
                    Text('Your Exclusive Referral Link', style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),

                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Referral Code:', style: GoogleFonts.inter(fontSize: 13, color: AppColors.textSecondary)),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: AppColors.background,
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: AppColors.border),
                                ),
                                child: Text(
                                  refData.referralCode,
                                  style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.primary),
                                ),
                              ),
                            ],
                          ),
                          const Divider(height: 20),
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  refData.referralLink,
                                  style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              IconButton(
                                icon: const Icon(Icons.copy, size: 20),
                                onPressed: () {
                                  Clipboard.setData(ClipboardData(text: refData.referralLink));
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    const SnackBar(content: Text('Referral link copied!')),
                                  );
                                },
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          ElevatedButton.icon(
                            onPressed: () {
                              Share.share('Join Taskwala with my code ${refData.referralCode}: ${refData.referralLink}');
                            },
                            icon: const Icon(Icons.share, size: 18),
                            label: const Text('Share Referral Link'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('Error: $err')),
            ),

            // Team Tab
            teamAsync.when(
              data: (members) {
                if (members.isEmpty) {
                  return Center(
                    child: Text('No team members referred yet.', style: GoogleFonts.inter(color: AppColors.textMuted)),
                  );
                }
                return ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: members.length,
                  itemBuilder: (context, index) {
                    final m = members[index];
                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: AppColors.primaryLight,
                            child: Text(m.name[0], style: const TextStyle(fontWeight: FontWeight.bold)),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(m.name, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 14)),
                                Text('Joined: ${m.joinedDate}', style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text('₹${m.referralEarnings.toStringAsFixed(0)}',
                                  style: GoogleFonts.inter(fontWeight: FontWeight.bold, color: AppColors.success, fontSize: 14)),
                              Text('${m.qualifyingConversions} Conversions',
                                  style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted)),
                            ],
                          ),
                        ],
                      ),
                    );
                  },
                );
              },
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (err, _) => Center(child: Text('Error: $err')),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatBox(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 6),
              Text(label, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
            ],
          ),
          const SizedBox(height: 6),
          Text(value, style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
