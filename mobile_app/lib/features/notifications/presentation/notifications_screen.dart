import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/bouncing_card.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  String _selectedTab = 'All';

  final List<Map<String, dynamic>> _sampleNotifications = [
    {
      'title': 'Payout Processed Successfully!',
      'body': 'Your payout request of ₹1,500 has been credited to your verified UPI ID.',
      'time': '10 mins ago',
      'icon': Icons.check_circle_rounded,
      'fgColor': AppColors.mint,
      'bgColor': AppColors.mintBg,
      'category': 'Earnings',
      'isUnread': true,
    },
    {
      'title': 'New Lead Conversion Approved',
      'body': 'HDFC Bank Credit Card conversion confirmed. +₹800 added to your approved wallet.',
      'time': '2 hours ago',
      'icon': Icons.monetization_on_rounded,
      'fgColor': AppColors.primary,
      'bgColor': AppColors.primaryLight,
      'category': 'Earnings',
      'isUnread': true,
    },
    {
      'title': 'Referral Override Credited',
      'body': 'Your referral partner Rahul completed a conversion! +₹80 credited to you.',
      'time': 'Yesterday',
      'icon': Icons.stars_rounded,
      'fgColor': AppColors.lavender,
      'bgColor': AppColors.lavenderBg,
      'category': 'Referrals',
      'isUnread': false,
    },
    {
      'title': 'High Demand Campaign Added',
      'body': 'Axis Bank Savings Account campaign is now live with enhanced ₹650 payout.',
      'time': '2 days ago',
      'icon': Icons.local_fire_department_rounded,
      'fgColor': AppColors.coral,
      'bgColor': AppColors.coralBg,
      'category': 'Offers',
      'isUnread': false,
    },
  ];

  @override
  Widget build(BuildContext context) {
    final filtered = _sampleNotifications.where((n) {
      if (_selectedTab == 'All') return true;
      return n['category'] == _selectedTab;
    }).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Text(
          'Notifications',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: AppColors.textPrimary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () {
              setState(() {
                for (var n in _sampleNotifications) {
                  n['isUnread'] = false;
                }
              });
            },
            child: Text(
              'Mark all read',
              style: GoogleFonts.plusJakartaSans(
                color: AppColors.primary,
                fontWeight: FontWeight.w700,
                fontSize: 12,
              ),
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          // Filter Tabs
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(bottom: BorderSide(color: AppColors.borderSubtle)),
            ),
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              child: Row(
                children: ['All', 'Earnings', 'Referrals', 'Offers'].map((tab) {
                  final isSelected = _selectedTab == tab;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8.0),
                    child: BouncingCard(
                      onTap: () => setState(() => _selectedTab = tab),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        curve: Curves.easeOutCubic,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.primary : AppColors.surfaceSecondary,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: isSelected ? AppColors.primary : AppColors.border,
                            width: 1,
                          ),
                        ),
                        child: Text(
                          tab,
                          style: GoogleFonts.plusJakartaSans(
                            color: isSelected ? Colors.white : AppColors.textSecondary,
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),

          Expanded(
            child: filtered.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.notifications_off_outlined, size: 48, color: AppColors.textMuted),
                        const SizedBox(height: 12),
                        Text(
                          'No notifications in this tab',
                          style: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  )
                : ListView.builder(
                    physics: const BouncingScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: filtered.length,
                    itemBuilder: (context, index) {
                      final item = filtered[index];
                      final isUnread = item['isUnread'] as bool;

                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: isUnread ? Colors.white : AppColors.surface,
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(
                            color: isUnread
                                ? AppColors.primary.withValues(alpha: 0.3)
                                : AppColors.border,
                            width: isUnread ? 1.4 : 1,
                          ),
                          boxShadow: AppShadows.soft,
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: item['bgColor'] as Color,
                                borderRadius: BorderRadius.circular(14),
                              ),
                              child: Icon(item['icon'] as IconData, color: item['fgColor'] as Color, size: 22),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          item['title'] as String,
                                          style: GoogleFonts.plusJakartaSans(
                                            fontWeight: FontWeight.w700,
                                            fontSize: 14,
                                            color: AppColors.textPrimary,
                                          ),
                                        ),
                                      ),
                                      if (isUnread)
                                        Container(
                                          width: 8,
                                          height: 8,
                                          decoration: const BoxDecoration(
                                            color: AppColors.primary,
                                            shape: BoxShape.circle,
                                          ),
                                        ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item['body'] as String,
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 12,
                                      color: AppColors.textSecondary,
                                      height: 1.35,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    item['time'] as String,
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w500,
                                      color: AppColors.textMuted,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ).animate().fadeIn(delay: (index * 40).ms, duration: 300.ms).slideY(begin: 0.05, end: 0);
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
