import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../core/theme/app_theme.dart';

class SupportScreen extends StatelessWidget {
  const SupportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Help & Support'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.primary.withAlpha(40)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.support_agent, color: AppColors.primary, size: 40),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('We are here to help', style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.primary)),
                        Text('Get in touch with partner support for campaign or payout queries.',
                            style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Text('Frequently Asked Questions', style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            _buildFaqItem('How do I track my conversions?', 'Conversions are updated automatically when reported by advertisers.'),
            _buildFaqItem('What is the minimum withdrawal limit?', 'Minimum payout threshold is ₹1.00 via verified UPI ID.'),
            _buildFaqItem('How does referral earning work?', 'You earn rewards when your referred partner completes qualifying campaign conversions.'),
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
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: ExpansionTile(
        title: Text(question, style: GoogleFonts.inter(fontWeight: FontWeight.w600, fontSize: 14)),
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: Text(answer, style: GoogleFonts.inter(fontSize: 13, color: AppColors.textSecondary)),
          ),
        ],
      ),
    );
  }
}
