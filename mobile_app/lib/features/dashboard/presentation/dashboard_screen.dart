import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../shared/providers/auth_provider.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../shared/models/models.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/skeleton_loader.dart';
import '../../../shared/widgets/glass_card.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/animated_counter.dart';
import '../../../shared/widgets/category_badge.dart';
import '../../../shared/widgets/campaign_logo.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;
    final walletAsync = ref.watch(walletProvider);
    final campaignsAsync = ref.watch(campaignsProvider);
    final clicksAsync = ref.watch(clickReportsProvider);
    final conversionsAsync = ref.watch(conversionReportsProvider);

    final totalClicksCount = clicksAsync.asData?.value.length ?? 0;
    final totalConversionsCount = conversionsAsync.asData?.value.length ?? 0;
    final convRateVal = totalClicksCount > 0
        ? (totalConversionsCount / totalClicksCount * 100)
        : 0.0;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.primaryGradientStart, AppColors.primaryGradientEnd],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(14),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.25),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              alignment: Alignment.center,
              child: Text(
                user?.name.isNotEmpty == true ? user!.name[0].toUpperCase() : 'S',
                style: GoogleFonts.plusJakartaSans(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 18,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          'Hi, ${user?.name ?? 'Partner'}',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Text('👋', style: TextStyle(fontSize: 14)),
                    ],
                  ),
                  Text(
                    'Welcome back & happy earning!',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          BouncingCard(
            onTap: () => context.go('/campaigns'),
            child: Container(
              margin: const EdgeInsets.only(right: 6),
              padding: const EdgeInsets.all(9),
              decoration: BoxDecoration(
                color: AppColors.surfaceSecondary,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.borderSubtle),
              ),
              child: const Icon(Icons.search_rounded, size: 20, color: AppColors.textPrimary),
            ),
          ),
          BouncingCard(
            onTap: () => context.push('/notifications'),
            child: Container(
              margin: const EdgeInsets.only(right: 16),
              padding: const EdgeInsets.all(9),
              decoration: BoxDecoration(
                color: AppColors.surfaceSecondary,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.borderSubtle),
              ),
              child: Stack(
                children: [
                  const Icon(Icons.notifications_none_rounded, size: 20, color: AppColors.textPrimary),
                  Positioned(
                    top: 1,
                    right: 1,
                    child: Container(
                      width: 7,
                      height: 7,
                      decoration: const BoxDecoration(
                        color: AppColors.coral,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () async {
          ref.invalidate(walletProvider);
          ref.invalidate(campaignsProvider);
          ref.invalidate(clickReportsProvider);
          ref.invalidate(conversionReportsProvider);
        },
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Vibrant Gradient Hero Balance Card
              walletAsync.when(
                data: (wallet) => _buildHeroWalletCard(context, wallet),
                loading: () => const SkeletonLoader(height: 180, borderRadius: 24),
                error: (_, __) => _buildHeroWalletCard(
                  context,
                  WalletData(
                    availableBalance: 0,
                    pendingBalance: 0,
                    approvedBalance: 0,
                    totalEarnings: 0,
                    totalPaid: 0,
                    upiStatus: 'not_added',
                    transactions: [],
                  ),
                ),
              ),

              const SizedBox(height: 18),

              // 3 Mini Metric Cards with Friendly Tints & Staggered Animations
              Row(
                children: [
                  Expanded(
                    child: _buildColorfulMetricCard(
                      label: 'Total Clicks',
                      value: totalClicksCount.toDouble(),
                      icon: Icons.touch_app_rounded,
                      fgColor: AppColors.sky,
                      bgColor: AppColors.skyBg,
                      prefix: '',
                      suffix: '',
                    ).animate().fadeIn(delay: 80.ms, duration: 400.ms).slideY(begin: 0.1, end: 0),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildColorfulMetricCard(
                      label: 'Conversions',
                      value: totalConversionsCount.toDouble(),
                      icon: Icons.check_circle_rounded,
                      fgColor: AppColors.mint,
                      bgColor: AppColors.mintBg,
                      prefix: '',
                      suffix: '',
                    ).animate().fadeIn(delay: 160.ms, duration: 400.ms).slideY(begin: 0.1, end: 0),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _buildColorfulMetricCard(
                      label: 'Conv. Rate',
                      value: convRateVal,
                      icon: Icons.trending_up_rounded,
                      fgColor: AppColors.coral,
                      bgColor: AppColors.coralBg,
                      prefix: '',
                      suffix: '%',
                      decimals: 1,
                    ).animate().fadeIn(delay: 240.ms, duration: 400.ms).slideY(begin: 0.1, end: 0),
                  ),
                ],
              ),

              const SizedBox(height: 24),

              // Quick Actions Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Quick Actions',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildQuickActionItem(
                    label: 'Offers',
                    icon: Icons.local_fire_department_rounded,
                    fgColor: AppColors.coral,
                    bgColor: AppColors.coralBg,
                    onTap: () => context.go('/campaigns'),
                  ),
                  _buildQuickActionItem(
                    label: 'My Links',
                    icon: Icons.link_rounded,
                    fgColor: AppColors.primary,
                    bgColor: AppColors.primaryLight,
                    onTap: () => context.go('/links'),
                  ),
                  _buildQuickActionItem(
                    label: 'Reports',
                    icon: Icons.bar_chart_rounded,
                    fgColor: AppColors.mint,
                    bgColor: AppColors.mintBg,
                    onTap: () => context.go('/reports'),
                  ),
                  _buildQuickActionItem(
                    label: 'Refer & Earn',
                    icon: Icons.stars_rounded,
                    fgColor: AppColors.lavender,
                    bgColor: AppColors.lavenderBg,
                    onTap: () => context.push('/referral'),
                  ),
                ],
              ).animate().fadeIn(delay: 200.ms, duration: 400.ms),

              const SizedBox(height: 24),

              // Performance Trend Chart Card
              GlassCard(
                padding: const EdgeInsets.all(18),
                borderRadius: 22,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Weekly Performance',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 15,
                                fontWeight: FontWeight.w700,
                                color: AppColors.textPrimary,
                              ),
                            ),
                            Text(
                              'Clicks vs Conversions trend',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 11,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                        Row(
                          children: [
                            _buildChartLegend('Clicks', AppColors.primary),
                            const SizedBox(width: 12),
                            _buildChartLegend('Conversions', AppColors.mint),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      height: 160,
                      child: LineChart(
                        LineChartData(
                          gridData: FlGridData(
                            show: true,
                            drawVerticalLine: false,
                            horizontalInterval: 2,
                            getDrawingHorizontalLine: (value) => const FlLine(
                              color: AppColors.borderSubtle,
                              strokeWidth: 1,
                            ),
                          ),
                          titlesData: FlTitlesData(
                            leftTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                            bottomTitles: AxisTitles(
                              sideTitles: SideTitles(
                                showTitles: true,
                                interval: 1,
                                reservedSize: 22,
                                getTitlesWidget: (val, _) {
                                  const titles = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                  if (val.toInt() >= 0 && val.toInt() < titles.length) {
                                    return Padding(
                                      padding: const EdgeInsets.only(top: 6),
                                      child: Text(
                                        titles[val.toInt()],
                                        style: GoogleFonts.plusJakartaSans(
                                          fontSize: 10,
                                          fontWeight: FontWeight.w600,
                                          color: AppColors.textMuted,
                                        ),
                                      ),
                                    );
                                  }
                                  return const Text('');
                                },
                              ),
                            ),
                          ),
                          borderData: FlBorderData(show: false),
                          lineBarsData: [
                            LineChartBarData(
                              spots: const [
                                FlSpot(0, 1.2),
                                FlSpot(1, 3.4),
                                FlSpot(2, 2.8),
                                FlSpot(3, 5.2),
                                FlSpot(4, 4.3),
                                FlSpot(5, 6.8),
                                FlSpot(6, 6.1),
                              ],
                              isCurved: true,
                              curveSmoothness: 0.35,
                              color: AppColors.primary,
                              barWidth: 3,
                              isStrokeCapRound: true,
                              dotData: const FlDotData(show: false),
                              belowBarData: BarAreaData(
                                show: true,
                                gradient: LinearGradient(
                                  colors: [
                                    AppColors.primary.withValues(alpha: 0.18),
                                    AppColors.primary.withValues(alpha: 0.0),
                                  ],
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                ),
                              ),
                            ),
                            LineChartBarData(
                              spots: const [
                                FlSpot(0, 0.4),
                                FlSpot(1, 1.6),
                                FlSpot(2, 1.1),
                                FlSpot(3, 2.8),
                                FlSpot(4, 2.1),
                                FlSpot(5, 3.9),
                                FlSpot(6, 3.5),
                              ],
                              isCurved: true,
                              curveSmoothness: 0.35,
                              color: AppColors.mint,
                              barWidth: 2.5,
                              isStrokeCapRound: true,
                              dotData: const FlDotData(show: false),
                              belowBarData: BarAreaData(
                                show: true,
                                gradient: LinearGradient(
                                  colors: [
                                    AppColors.mint.withValues(alpha: 0.15),
                                    AppColors.mint.withValues(alpha: 0.0),
                                  ],
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ).animate().fadeIn(delay: 260.ms, duration: 400.ms),

              const SizedBox(height: 24),

              // Top High-Payout Offers Section
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Text(
                        'Top High-Payout Offers',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.coralBg,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          'HOT',
                          style: GoogleFonts.plusJakartaSans(
                            color: AppColors.coral,
                            fontSize: 9,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
                  TextButton(
                    onPressed: () => context.go('/campaigns'),
                    style: TextButton.styleFrom(
                      padding: EdgeInsets.zero,
                      minimumSize: const Size(50, 30),
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          'Explore',
                          style: GoogleFonts.plusJakartaSans(
                            color: AppColors.primary,
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                          ),
                        ),
                        const Icon(Icons.chevron_right_rounded, size: 16, color: AppColors.primary),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              campaignsAsync.when(
                data: (campaigns) {
                  if (campaigns.isEmpty) {
                    return GlassCard(
                      padding: const EdgeInsets.all(24),
                      child: Center(
                        child: Text(
                          'No campaigns available right now.',
                          style: GoogleFonts.plusJakartaSans(color: AppColors.textMuted),
                        ),
                      ),
                    );
                  }
                  return ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: campaigns.take(3).length,
                    itemBuilder: (context, index) {
                      final c = campaigns[index];

                      return BouncingCard(
                        onTap: () => context.push('/link-generator', extra: c),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: AppColors.surface,
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: AppColors.border),
                            boxShadow: AppShadows.soft,
                          ),
                          child: Row(
                            children: [
                              CampaignLogo(
                                logoUrl: c.logo,
                                name: c.name,
                                category: c.category,
                                size: 48,
                                borderRadius: 16,
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        CategoryBadge(label: c.category, fontSize: 10),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      c.name,
                                      style: GoogleFonts.plusJakartaSans(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 14,
                                        color: AppColors.textPrimary,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Row(
                                      children: [
                                        Text(
                                          'Earn up to ',
                                          style: GoogleFonts.plusJakartaSans(
                                            fontSize: 12,
                                            color: AppColors.textSecondary,
                                          ),
                                        ),
                                        Text(
                                          '₹${c.affiliatePayout.toStringAsFixed(0)}',
                                          style: GoogleFonts.plusJakartaSans(
                                            color: AppColors.mint,
                                            fontWeight: FontWeight.w800,
                                            fontSize: 13,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                decoration: BoxDecoration(
                                  color: AppColors.primaryLight,
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: Text(
                                  'Get Link',
                                  style: GoogleFonts.plusJakartaSans(
                                    color: AppColors.primary,
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ).animate().fadeIn(delay: (300 + index * 70).ms, duration: 350.ms).slideY(begin: 0.08, end: 0);
                    },
                  );
                },
                loading: () => Column(
                  children: List.generate(
                    3,
                    (index) => const Padding(
                      padding: EdgeInsets.only(bottom: 12.0),
                      child: SkeletonLoader(height: 76, borderRadius: 20),
                    ),
                  ),
                ),
                error: (_, __) => const SizedBox(),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeroWalletCard(BuildContext context, WalletData wallet) {
    return VibrantGradientCard(
      padding: const EdgeInsets.all(22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: const BoxDecoration(
                          color: Color(0xFF00F0FF),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        'Available Balance',
                        style: GoogleFonts.plusJakartaSans(
                          color: Colors.white.withValues(alpha: 0.85),
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  AnimatedCounter(
                    value: wallet.availableBalance,
                    prefix: '₹',
                    style: GoogleFonts.plusJakartaSans(
                      color: Colors.white,
                      fontSize: 34,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.5,
                    ),
                  ),
                ],
              ),
              BouncingCard(
                onTap: () => context.push('/payout'),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.12),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.arrow_outward_rounded, size: 16, color: AppColors.primary),
                      const SizedBox(width: 4),
                      Text(
                        'Withdraw',
                        style: GoogleFonts.plusJakartaSans(
                          color: AppColors.primary,
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.2),
                width: 1,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildBalanceSubStat('Total Earned', wallet.totalEarnings),
                Container(width: 1, height: 26, color: Colors.white.withValues(alpha: 0.25)),
                _buildBalanceSubStat('Pending', wallet.pendingBalance),
                Container(width: 1, height: 26, color: Colors.white.withValues(alpha: 0.25)),
                _buildBalanceSubStat('Approved', wallet.approvedBalance),
              ],
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.08, end: 0, curve: Curves.easeOutCubic);
  }

  Widget _buildBalanceSubStat(String label, double value) {
    return Column(
      children: [
        AnimatedCounter(
          value: value,
          prefix: '₹',
          style: GoogleFonts.plusJakartaSans(
            color: Colors.white,
            fontWeight: FontWeight.w800,
            fontSize: 14,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          style: GoogleFonts.plusJakartaSans(
            color: Colors.white.withValues(alpha: 0.75),
            fontSize: 10,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  Widget _buildColorfulMetricCard({
    required String label,
    required double value,
    required IconData icon,
    required Color fgColor,
    required Color bgColor,
    required String prefix,
    required String suffix,
    int decimals = 0,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.soft,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, size: 16, color: fgColor),
          ),
          const SizedBox(height: 10),
          AnimatedCounter(
            value: value,
            prefix: prefix,
            suffix: suffix,
            decimalDigits: decimals,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActionItem({
    required String label,
    required IconData icon,
    required Color fgColor,
    required Color bgColor,
    required VoidCallback onTap,
  }) {
    return BouncingCard(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            width: 58,
            height: 58,
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: fgColor.withValues(alpha: 0.18), width: 1),
              boxShadow: [
                BoxShadow(
                  color: fgColor.withValues(alpha: 0.12),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Icon(icon, color: fgColor, size: 26),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChartLegend(String label, Color color) {
    return Row(
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }
}
