import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/bouncing_card.dart';

class SupportScreen extends StatelessWidget {
  const SupportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Text(
          'Help & Support',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: AppColors.textPrimary,
          ),
        ),
      ),
      body: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Friendly Support Hero Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.primaryGradientStart, AppColors.primaryGradientEnd],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(24),
                boxShadow: AppShadows.primary,
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.support_agent_rounded, color: Colors.white, size: 36),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'We are here to help!',
                          style: GoogleFonts.plusJakartaSans(
                            fontWeight: FontWeight.w800,
                            fontSize: 17,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Instant partner assistance for payout, campaign, and tracking queries.',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 11,
                            color: Colors.white.withValues(alpha: 0.85),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ).animate().fadeIn(duration: 350.ms).slideY(begin: 0.08, end: 0),

            const SizedBox(height: 20),

            // Quick Contact Options
            Row(
              children: [
                Expanded(
                  child: _buildContactChannel(
                    icon: Icons.chat_rounded,
                    label: 'WhatsApp',
                    subtitle: 'Quick Chat',
                    fgColor: AppColors.mint,
                    bgColor: AppColors.mintBg,
                    onTap: () {},
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _buildContactChannel(
                    icon: Icons.call_rounded,
                    label: 'Call Us',
                    subtitle: '10 AM - 7 PM',
                    fgColor: AppColors.sky,
                    bgColor: AppColors.skyBg,
                    onTap: () {},
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _buildContactChannel(
                    icon: Icons.mail_rounded,
                    label: 'Email',
                    subtitle: '24h SLA',
                    fgColor: AppColors.coral,
                    bgColor: AppColors.coralBg,
                    onTap: () {},
                  ),
                ),
              ],
            ).animate().fadeIn(delay: 100.ms, duration: 350.ms),

            const SizedBox(height: 24),
            Text(
              'Frequently Asked Questions',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),

            _buildFaqItem(
              'How are lead conversions tracked?',
              'Conversions are tracked in real-time when a user clicks your affiliate link and completes the qualifying action on the partner advertiser portal.',
            ).animate().fadeIn(delay: 150.ms, duration: 300.ms),
            _buildFaqItem(
              'What is the minimum UPI withdrawal limit?',
              'The minimum payout withdrawal threshold is ₹500. Withdrawals are processed directly into your verified UPI VPA.',
            ).animate().fadeIn(delay: 200.ms, duration: 300.ms),
            _buildFaqItem(
              'How does referral commission work?',
              'You earn a 10% lifetime commission bonus on all qualifying earnings generated by partners who sign up with your referral code.',
            ).animate().fadeIn(delay: 250.ms, duration: 300.ms),
            _buildFaqItem(
              'Can I customize customer incentive payouts?',
              'Yes! In the Link Generator, you can split your total commission payout with the customer to offer attractive cashback.',
            ).animate().fadeIn(delay: 300.ms, duration: 300.ms),
          ],
        ),
      ),
    );
  }

  Widget _buildContactChannel({
    required IconData icon,
    required String label,
    required String subtitle,
    required Color fgColor,
    required Color bgColor,
    required VoidCallback onTap,
  }) {
    return BouncingCard(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.border),
          boxShadow: AppShadows.soft,
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(9),
              decoration: BoxDecoration(
                color: bgColor,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: fgColor, size: 20),
            ),
            const SizedBox(height: 8),
            Text(
              label,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              subtitle,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 10,
                color: AppColors.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFaqItem(String question, String answer) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.soft,
      ),
      child: Theme(
        data: ThemeData().copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          collapsedShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          iconColor: AppColors.primary,
          collapsedIconColor: AppColors.textMuted,
          title: Text(
            question,
            style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w700,
              fontSize: 13,
              color: AppColors.textPrimary,
            ),
          ),
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: Text(
                answer,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                  height: 1.45,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
