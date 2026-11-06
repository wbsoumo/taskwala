import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/models/models.dart';
import '../../../shared/widgets/empty_state.dart';
import '../../../shared/widgets/category_badge.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/skeleton_loader.dart';
import '../../../shared/widgets/campaign_logo.dart';

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});

  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  // Tabs: 'conversions' or 'clicks'
  String _activeTab = 'conversions';

  // Search
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  // Filters
  String _selectedConversionStatus = 'All';
  String _selectedClickStatus = 'All';
  String _selectedDateRange = 'All Time';

  final List<String> _dateRanges = ['All Time', 'Today', 'This Week', 'This Month'];
  final List<String> _conversionStatuses = ['All', 'Approved', 'Pending', 'Rejected'];
  final List<String> _clickStatuses = ['All', 'Converted', 'Valid', 'Duplicate'];

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  bool _matchesDateRange(String dateStr, String range) {
    if (range == 'All Time' || dateStr.isEmpty) return true;
    try {
      final dt = DateTime.tryParse(dateStr);
      if (dt == null) return true;
      final now = DateTime.now();
      if (range == 'Today') {
        return dt.year == now.year && dt.month == now.month && dt.day == now.day;
      } else if (range == 'This Week') {
        return now.difference(dt).inDays <= 7;
      } else if (range == 'This Month') {
        return now.difference(dt).inDays <= 30;
      }
    } catch (_) {}
    return true;
  }

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    HapticFeedback.lightImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: const Color(0xFF0F172A),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        duration: const Duration(seconds: 2),
        content: Text(
          '$label copied to clipboard',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
      ),
    );
  }

  void _resetFilters() {
    setState(() {
      _searchController.clear();
      _searchQuery = '';
      _selectedConversionStatus = 'All';
      _selectedClickStatus = 'All';
      _selectedDateRange = 'All Time';
    });
  }

  @override
  Widget build(BuildContext context) {
    final conversionsAsync = ref.watch(conversionReportsProvider);
    final clicksAsync = ref.watch(clickReportsProvider);

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
              'Reports & Analytics',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: AppColors.textPrimary,
              ),
            ),
            Text(
              'Real-time conversion tracking & traffic insights',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Claim Missing Lead',
            icon: const Icon(Icons.assignment_late_outlined, size: 21, color: AppColors.primary),
            onPressed: () {
              HapticFeedback.lightImpact();
              context.push('/tickets');
            },
          ),
          IconButton(
            tooltip: 'Refresh Reports',
            icon: const Icon(Icons.refresh_rounded, size: 21, color: AppColors.textPrimary),
            onPressed: () {
              HapticFeedback.lightImpact();
              ref.invalidate(conversionReportsProvider);
              ref.invalidate(clickReportsProvider);
            },
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: Column(
        children: [
          // TOP SEGMENTED TAB SWITCHER (Conversions vs Clicks)
          Container(
            color: AppColors.surface,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
            child: Container(
              height: 44,
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.surfaceSecondary,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.borderSubtle),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: _buildSegmentButton(
                      id: 'conversions',
                      label: 'Conversions',
                      icon: Icons.check_circle_outline_rounded,
                      isActive: _activeTab == 'conversions',
                    ),
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: _buildSegmentButton(
                      id: 'clicks',
                      label: 'Click Traffic',
                      icon: Icons.ads_click_rounded,
                      isActive: _activeTab == 'clicks',
                    ),
                  ),
                ],
              ),
            ),
          ),

          // MISSING CONVERSION CLAIM BANNER
          Container(
            color: AppColors.surface,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 10),
            child: InkWell(
              onTap: () => context.push('/tickets'),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFEFF6FF), Color(0xFFDBEAFE)],
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFBFDBFE)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: const BoxDecoration(
                        color: Color(0xFF2563EB),
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 14),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Missing a lead or payout?',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: const Color(0xFF1E3A8A),
                            ),
                          ),
                          Text(
                            'Tap here to submit Order ID proof & claim wallet credit',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 10.5,
                              color: const Color(0xFF1E40AF),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const Icon(Icons.arrow_forward_ios_rounded, color: Color(0xFF2563EB), size: 13),
                  ],
                ),
              ),
            ),
          ),

          // SEARCH BAR & FILTER PILLS CONTAINER
          Container(
            color: AppColors.surface,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: Column(
              children: [
                // Real-time Search Input
                Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: AppColors.surfaceSecondary,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: _searchQuery.isNotEmpty ? AppColors.primary : AppColors.border,
                      width: 1,
                    ),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (val) => setState(() => _searchQuery = val.trim()),
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                    decoration: InputDecoration(
                      hintText: _activeTab == 'conversions'
                          ? 'Search by campaign, status, or Ref ID...'
                          : 'Search by campaign, IP, or click ID...',
                      hintStyle: GoogleFonts.plusJakartaSans(
                        fontSize: 12,
                        color: AppColors.textMuted,
                      ),
                      prefixIcon: const Icon(Icons.search_rounded, size: 18, color: AppColors.textSecondary),
                      suffixIcon: _searchQuery.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.close_rounded, size: 16, color: AppColors.textSecondary),
                              onPressed: () {
                                _searchController.clear();
                                setState(() => _searchQuery = '');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                // Horizontal Filter Row: Status Filters & Date Range
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(),
                  child: Row(
                    children: [
                      // Status Filters
                      if (_activeTab == 'conversions')
                        ..._conversionStatuses.map((st) {
                          final isSelected = _selectedConversionStatus == st;
                          return Padding(
                            padding: const EdgeInsets.only(right: 6.0),
                            child: BouncingCard(
                              onTap: () => setState(() => _selectedConversionStatus = st),
                              child: _buildFilterChip(label: st, isSelected: isSelected),
                            ),
                          );
                        })
                      else
                        ..._clickStatuses.map((st) {
                          final isSelected = _selectedClickStatus == st;
                          return Padding(
                            padding: const EdgeInsets.only(right: 6.0),
                            child: BouncingCard(
                              onTap: () => setState(() => _selectedClickStatus = st),
                              child: _buildFilterChip(label: st, isSelected: isSelected),
                            ),
                          );
                        }),

                      // Vertical Divider
                      Container(
                        height: 18,
                        width: 1,
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        color: AppColors.border,
                      ),

                      // Date Range Filter Dropdown / Pills
                      ..._dateRanges.map((range) {
                        final isSelected = _selectedDateRange == range;
                        return Padding(
                          padding: const EdgeInsets.only(left: 6.0),
                          child: BouncingCard(
                            onTap: () => setState(() => _selectedDateRange = range),
                            child: _buildFilterChip(
                              label: range,
                              isSelected: isSelected,
                              isSecondary: true,
                            ),
                          ),
                        );
                      }),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // LIST CONTENT OR ANALYTICS SUMMARY
          Expanded(
            child: _activeTab == 'conversions'
                ? _buildConversionsContent(conversionsAsync)
                : _buildClicksContent(clicksAsync),
          ),
        ],
      ),
    );
  }

  Widget _buildSegmentButton({
    required String id,
    required String label,
    required IconData icon,
    required bool isActive,
  }) {
    return BouncingCard(
      onTap: () {
        if (_activeTab != id) {
          HapticFeedback.selectionClick();
          setState(() => _activeTab = id);
        }
      },
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        curve: Curves.easeOutCubic,
        decoration: BoxDecoration(
          color: isActive ? AppColors.surface : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
          boxShadow: isActive
              ? [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.05),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        alignment: Alignment.center,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              size: 16,
              color: isActive ? AppColors.primary : AppColors.textSecondary,
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 12,
                fontWeight: isActive ? FontWeight.w800 : FontWeight.w600,
                color: isActive ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip({
    required String label,
    required bool isSelected,
    bool isSecondary = false,
  }) {
    final activeBg = isSecondary ? const Color(0xFF0F172A) : AppColors.primary;
    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: isSelected ? activeBg : AppColors.surfaceSecondary,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isSelected ? activeBg : AppColors.border,
          width: 1,
        ),
      ),
      child: Text(
        label,
        style: GoogleFonts.plusJakartaSans(
          fontSize: 11,
          fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
          color: isSelected ? Colors.white : AppColors.textSecondary,
        ),
      ),
    );
  }

  // CONVERSIONS LIST & METRICS
  Widget _buildConversionsContent(AsyncValue<List<ConversionReport>> conversionsAsync) {
    return conversionsAsync.when(
      data: (conversions) {
        // Filter logic
        final filtered = conversions.where((c) {
          // Status
          if (_selectedConversionStatus != 'All' &&
              !c.status.toLowerCase().contains(_selectedConversionStatus.toLowerCase())) {
            return false;
          }
          // Date
          if (!_matchesDateRange(c.createdAt, _selectedDateRange)) {
            return false;
          }
          // Search
          if (_searchQuery.isNotEmpty) {
            final q = _searchQuery.toLowerCase();
            final matchCamp = c.campaignName.toLowerCase().contains(q);
            final matchCat = c.category.toLowerCase().contains(q);
            final matchRef = c.referenceId.toLowerCase().contains(q);
            final matchSt = c.status.toLowerCase().contains(q);
            if (!matchCamp && !matchCat && !matchRef && !matchSt) return false;
          }
          return true;
        }).toList();

        // Calculate Metrics
        final totalCount = filtered.length;
        final approvedEarnings = filtered
            .where((c) => c.status.toLowerCase() == 'approved')
            .fold(0.0, (acc, c) => acc + c.commission);
        final pendingEarnings = filtered
            .where((c) => c.status.toLowerCase() == 'pending')
            .fold(0.0, (acc, c) => acc + c.commission);

        return RefreshIndicator(
          onRefresh: () async => ref.refresh(conversionReportsProvider.future),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
            children: [
              // Summary Metrics Bar
              _buildMetricsSummaryRow([
                _MetricItem(label: 'Conversions', value: '$totalCount', icon: Icons.insights_rounded, color: AppColors.primary),
                _MetricItem(label: 'Approved', value: '₹${approvedEarnings.toStringAsFixed(0)}', icon: Icons.verified_rounded, color: AppColors.mint),
                _MetricItem(label: 'Pending', value: '₹${pendingEarnings.toStringAsFixed(0)}', icon: Icons.schedule_rounded, color: Colors.amber.shade700),
              ]),

              const SizedBox(height: 14),

              if (filtered.isEmpty)
                _buildEmptyResultsState('No conversions match your filters.')
              else
                ...filtered.asMap().entries.map((entry) {
                  final index = entry.key;
                  final c = entry.value;
                  return _buildConversionCard(c, index);
                }),
            ],
          ),
        );
      },
      loading: () => _buildLoadingList(),
      error: (err, _) => _buildErrorState('Failed to load conversions: $err'),
    );
  }

  // CLICKS LIST & METRICS
  Widget _buildClicksContent(AsyncValue<List<ClickReport>> clicksAsync) {
    return clicksAsync.when(
      data: (clicks) {
        // Filter logic
        final filtered = clicks.where((c) {
          // Status
          if (_selectedClickStatus != 'All') {
            final st = _selectedClickStatus.toLowerCase();
            if (st == 'converted' && !c.isConverted) return false;
            if (st != 'converted' && !c.status.toLowerCase().contains(st)) return false;
          }
          // Date
          if (!_matchesDateRange(c.clickedAt, _selectedDateRange)) {
            return false;
          }
          // Search
          if (_searchQuery.isNotEmpty) {
            final q = _searchQuery.toLowerCase();
            final matchCamp = c.campaignName.toLowerCase().contains(q);
            final matchCat = c.category.toLowerCase().contains(q);
            final matchLink = c.linkCode.toLowerCase().contains(q);
            final matchIp = c.ipAddress.toLowerCase().contains(q);
            if (!matchCamp && !matchCat && !matchLink && !matchIp) return false;
          }
          return true;
        }).toList();

        // Calculate Metrics
        final totalClicks = filtered.length;
        final convertedClicks = filtered.where((c) => c.isConverted).length;
        final convRate = totalClicks > 0 ? ((convertedClicks / totalClicks) * 100).toStringAsFixed(1) : '0.0';

        return RefreshIndicator(
          onRefresh: () async => ref.refresh(clickReportsProvider.future),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 100),
            children: [
              // Summary Metrics Bar
              _buildMetricsSummaryRow([
                _MetricItem(label: 'Total Clicks', value: '$totalClicks', icon: Icons.ads_click_rounded, color: AppColors.primary),
                _MetricItem(label: 'Converted', value: '$convertedClicks', icon: Icons.check_circle_rounded, color: AppColors.mint),
                _MetricItem(label: 'Conv. Rate', value: '$convRate%', icon: Icons.trending_up_rounded, color: const Color(0xFF6366F1)),
              ]),

              const SizedBox(height: 14),

              if (filtered.isEmpty)
                _buildEmptyResultsState('No click traffic matches your filters.')
              else
                ...filtered.asMap().entries.map((entry) {
                  final index = entry.key;
                  final c = entry.value;
                  return _buildClickCard(c, index);
                }),
            ],
          ),
        );
      },
      loading: () => _buildLoadingList(),
      error: (err, _) => _buildErrorState('Failed to load click reports: $err'),
    );
  }

  Widget _buildMetricsSummaryRow(List<_MetricItem> items) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.soft,
      ),
      child: Row(
        children: items.asMap().entries.map((entry) {
          final index = entry.key;
          final item = entry.value;
          return Expanded(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(item.icon, size: 13, color: item.color),
                          const SizedBox(width: 4),
                          Flexible(
                            child: Text(
                              item.label,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textSecondary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 3),
                      Text(
                        item.value,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                ),
                if (index < items.length - 1)
                  Container(
                    height: 28,
                    width: 1,
                    margin: const EdgeInsets.symmetric(horizontal: 8),
                    color: AppColors.borderSubtle,
                  ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildConversionCard(ConversionReport c, int index) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
        boxShadow: AppShadows.soft,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Campaign Logo
              CampaignLogo(
                logoUrl: c.campaignLogo,
                name: c.campaignName,
                category: c.category,
                size: 46,
                borderRadius: 14,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        CategoryBadge(label: c.category, fontSize: 10),
                        StatusBadge(status: c.status),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      c.campaignName,
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Payout split detail bar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.surfaceSecondary,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Text(
                      'Commission: ',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        color: AppColors.textSecondary,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    Text(
                      '₹${c.commission.toStringAsFixed(0)}',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: AppColors.mint,
                      ),
                    ),
                  ],
                ),
                if (c.customerPayout > 0)
                  Text(
                    'Cashback: ₹${c.customerPayout.toStringAsFixed(0)}',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textSecondary,
                    ),
                  ),
              ],
            ),
          ),

          const SizedBox(height: 10),

          // Reference ID & Date
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              InkWell(
                onTap: () => _copyToClipboard(c.referenceId, 'Reference ID'),
                borderRadius: BorderRadius.circular(6),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 4),
                  child: Row(
                    children: [
                      Text(
                        'Ref: ${c.referenceId.length > 14 ? '${c.referenceId.substring(0, 14)}...' : c.referenceId}',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textMuted,
                        ),
                      ),
                      const SizedBox(width: 4),
                      const Icon(Icons.copy_rounded, size: 12, color: AppColors.textMuted),
                    ],
                  ),
                ),
              ),
              Text(
                c.createdAt,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: AppColors.textMuted,
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: (index * 30).ms, duration: 250.ms).slideY(begin: 0.04, end: 0);
  }

  Widget _buildClickCard(ClickReport c, int index) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: c.isConverted ? AppColors.mint.withValues(alpha: 0.3) : AppColors.border,
        ),
        boxShadow: AppShadows.soft,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Campaign Logo
              CampaignLogo(
                logoUrl: c.campaignLogo,
                name: c.campaignName,
                category: c.category,
                size: 46,
                borderRadius: 14,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        CategoryBadge(label: c.category, fontSize: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: c.isConverted ? AppColors.mintBg : AppColors.surfaceSecondary,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: c.isConverted ? AppColors.mint.withValues(alpha: 0.3) : AppColors.borderSubtle,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                c.isConverted ? Icons.check_circle_rounded : Icons.radio_button_checked_rounded,
                                size: 11,
                                color: c.isConverted ? AppColors.mint : AppColors.primary,
                              ),
                              const SizedBox(width: 4),
                              Text(
                                c.isConverted ? 'Converted' : c.status.toUpperCase(),
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: c.isConverted ? AppColors.mint : AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      c.campaignName,
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 14,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 12),

          // Link Code & IP Info Row
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.surfaceSecondary,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    const Icon(Icons.link_rounded, size: 14, color: AppColors.primary),
                    const SizedBox(width: 6),
                    Text(
                      'Link: ${c.linkCode}',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
                Text(
                  'IP: ${c.ipAddress}',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    color: AppColors.textMuted,
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 10),

          // Date & ID
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Click #${c.id}',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textMuted,
                ),
              ),
              Text(
                c.clickedAt,
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: AppColors.textMuted,
                ),
              ),
            ],
          ),
        ],
      ),
    ).animate().fadeIn(delay: (index * 30).ms, duration: 250.ms).slideY(begin: 0.04, end: 0);
  }

  Widget _buildEmptyResultsState(String message) {
    return Padding(
      padding: const EdgeInsets.only(top: 40.0),
      child: Column(
        children: [
          const TaskwalaEmptyState(
            icon: Icons.filter_list_off_rounded,
            title: 'No Matching Records',
            message: 'Try clearing your search terms or adjusting the date and status filters.',
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _resetFilters,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.surfaceSecondary,
              foregroundColor: AppColors.primary,
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
                side: const BorderSide(color: AppColors.border),
              ),
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
            ),
            icon: const Icon(Icons.replay_rounded, size: 16),
            label: Text(
              'Reset All Filters',
              style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadingList() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: 4,
      itemBuilder: (_, __) => const Padding(
        padding: EdgeInsets.only(bottom: 12),
        child: SkeletonLoader(height: 115, borderRadius: 20),
      ),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.danger),
            const SizedBox(height: 12),
            Text(
              error,
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                color: AppColors.danger,
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () {
                ref.invalidate(conversionReportsProvider);
                ref.invalidate(clickReportsProvider);
              },
              child: const Text('Try Again'),
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricItem {
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  _MetricItem({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });
}
