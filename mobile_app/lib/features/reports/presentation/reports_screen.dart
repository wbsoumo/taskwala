import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/empty_state.dart';

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});

  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  String _selectedFilter = 'All';

  @override
  Widget build(BuildContext context) {
    final conversionsAsync = ref.watch(conversionReportsProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: Text('Conversions', style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      body: Column(
        children: [
          // Filter Chips (All, Pending, Approved) matching reference UI
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            color: AppColors.surface,
            child: SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: ['All (85)', 'Pending (12)', 'Approved (53)', 'Rejected (20)'].map((filter) {
                  final label = filter.split(' ')[0];
                  final isSelected = _selectedFilter == label;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8.0),
                    child: ChoiceChip(
                      label: Text(filter),
                      selected: isSelected,
                      selectedColor: AppColors.primary,
                      backgroundColor: AppColors.background,
                      labelStyle: GoogleFonts.inter(
                        color: isSelected ? Colors.white : AppColors.textSecondary,
                        fontSize: 12,
                        fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                      ),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      onSelected: (_) => setState(() => _selectedFilter = label),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),

          // Conversions List Cards
          Expanded(
            child: conversionsAsync.when(
              data: (conversions) {
                if (conversions.isEmpty) {
                  return const TaskwalaEmptyState(
                    icon: Icons.assignment_turned_in_outlined,
                    title: 'No Conversions Yet',
                    message: 'Share your offer links to start generating lead conversions and earning commission.',
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
                                  c.campaignName[0],
                                  style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primary),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(c.campaignName, style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 14)),
                                    const SizedBox(height: 2),
                                    Text(
                                      '₹${c.customerPayout.toStringAsFixed(0)} (Customer) • ₹${c.commission.toStringAsFixed(0)} (You)',
                                      style: GoogleFonts.inter(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: isApproved
                                      ? AppColors.successBg
                                      : (isPending ? AppColors.pendingBg : AppColors.dangerBg),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Text(
                                  c.status.toUpperCase(),
                                  style: GoogleFonts.inter(
                                    color: isApproved
                                        ? AppColors.success
                                        : (isPending ? AppColors.pending : AppColors.danger),
                                    fontSize: 10,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  'Ref: ${c.referenceId}',
                                  style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const SizedBox(width: 8),
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
          ),
        ],
      ),
    );
  }
}
