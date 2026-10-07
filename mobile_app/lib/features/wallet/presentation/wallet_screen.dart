import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';

class WalletScreen extends ConsumerWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final walletAsync = ref.watch(walletProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Wallet & Ledger'),
      ),
      body: walletAsync.when(
        data: (wallet) {
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Summary Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Available Balance', style: GoogleFonts.inter(fontSize: 13, color: AppColors.textSecondary)),
                          Text('₹${wallet.availableBalance.toStringAsFixed(2)}',
                              style: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.primary)),
                        ],
                      ),
                      const Divider(height: 24),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Pending', style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
                                Text('₹${wallet.pendingBalance.toStringAsFixed(0)}',
                                    style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.warning)),
                              ],
                            ),
                          ),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Total Earnings', style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
                                Text('₹${wallet.totalEarnings.toStringAsFixed(0)}',
                                    style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.success)),
                              ],
                            ),
                          ),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Total Paid', style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary)),
                                Text('₹${wallet.totalPaid.toStringAsFixed(0)}',
                                    style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textPrimary)),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),
                Text('Transaction History', style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),

                wallet.transactions.isEmpty
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(32.0),
                          child: Text('No transactions recorded yet.', style: GoogleFonts.inter(color: AppColors.textMuted)),
                        ),
                      )
                    : ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: wallet.transactions.length,
                        itemBuilder: (context, index) {
                          final tx = wallet.transactions[index];
                          final isCredit = tx.type == 'earning' || tx.type == 'referral_earning';

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
                                  backgroundColor: (isCredit ? AppColors.success : AppColors.secondary).withAlpha(20),
                                  child: Icon(
                                    isCredit ? Icons.arrow_downward : Icons.arrow_upward,
                                    color: isCredit ? AppColors.success : AppColors.secondary,
                                    size: 18,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(tx.description, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 13)),
                                      Text(tx.createdAt, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted)),
                                    ],
                                  ),
                                ),
                                Text(
                                  '${isCredit ? '+' : '-'}₹${tx.amount.toStringAsFixed(0)}',
                                  style: GoogleFonts.inter(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15,
                                    color: isCredit ? AppColors.success : AppColors.danger,
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('Error loading wallet: $err')),
      ),
    );
  }
}
