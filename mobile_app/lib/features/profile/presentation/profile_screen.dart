import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final walletAsync = ref.watch(walletProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('My Profile'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // User Info Header
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 30,
                    backgroundColor: AppColors.primaryLight,
                    child: Text(
                      user?.name.isNotEmpty == true ? user!.name[0].toUpperCase() : 'U',
                      style: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.primary),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user?.name ?? 'User',
                          style: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          user?.email ?? '',
                          style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
                        ),
                        if (user?.mobile != null)
                          Text(
                            user!.mobile!,
                            style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Quick Options List
            _buildOptionTile(
              context,
              icon: Icons.account_balance_wallet_outlined,
              title: 'Wallet & Ledger',
              subtitle: 'Check balances and transaction history',
              onTap: () => context.push('/wallet'),
            ),

            _buildOptionTile(
              context,
              icon: Icons.payment_outlined,
              title: 'UPI & Payout Settings',
              subtitle: walletAsync.asData?.value.upiId != null
                  ? 'UPI: ${walletAsync.asData!.value.upiId}'
                  : 'Add your UPI ID for payouts',
              onTap: () => context.push('/payout'),
            ),

            _buildOptionTile(
              context,
              icon: Icons.group_add_outlined,
              title: 'Refer & Earn',
              subtitle: 'Invite partners and track team earnings',
              onTap: () => context.push('/referral'),
            ),

            _buildOptionTile(
              context,
              icon: Icons.support_agent_outlined,
              title: 'Help & Support',
              subtitle: 'FAQs, contact support, and issue reporting',
              onTap: () => context.push('/support'),
            ),

            const SizedBox(height: 24),

            // Logout Button
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () async {
                  await ref.read(authProvider.notifier).logout();
                  if (context.mounted) {
                    context.go('/login');
                  }
                },
                icon: const Icon(Icons.logout, color: AppColors.danger),
                label: const Text('Logout', style: TextStyle(color: AppColors.danger)),
                style: OutlinedButton.styleFrom(
                  side: const BorderSide(color: AppColors.danger),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildOptionTile(
    BuildContext context, {
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppColors.primaryLight,
          child: Icon(icon, color: AppColors.primary, size: 20),
        ),
        title: Text(title, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 14)),
        subtitle: Text(subtitle, style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
        trailing: const Icon(Icons.chevron_right, color: AppColors.textMuted),
        onTap: onTap,
      ),
    );
  }
}
