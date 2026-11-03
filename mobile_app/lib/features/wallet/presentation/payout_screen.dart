import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/animated_counter.dart';
import '../../../shared/widgets/skeleton_loader.dart';

class PayoutScreen extends ConsumerStatefulWidget {
  const PayoutScreen({super.key});

  @override
  ConsumerState<PayoutScreen> createState() => _PayoutScreenState();
}

class _PayoutScreenState extends ConsumerState<PayoutScreen> {
  final _amountController = TextEditingController();
  final _upiController = TextEditingController();
  bool _isSubmitting = false;
  bool _isSavingUpi = false;
  String? _message;
  bool _isSuccess = false;
  double? _selectedPresetAmount;

  @override
  void dispose() {
    _amountController.dispose();
    _upiController.dispose();
    super.dispose();
  }

  void _saveUpi() async {
    final upi = _upiController.text.trim();
    if (upi.isEmpty) return;

    setState(() => _isSavingUpi = true);
    final api = ref.read(apiClientProvider);

    try {
      final res = await api.post('/wallet/upi', data: {'upi_id': upi});
      if (mounted) {
        setState(() => _isSavingUpi = false);
        final msg = res.data is Map ? (res.data['message'] ?? 'UPI ID updated successfully!') : 'UPI ID updated!';
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.mint,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            content: Row(
              children: [
                const Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
                const SizedBox(width: 8),
                Text(
                  msg.toString(),
                  style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600, color: Colors.white),
                ),
              ],
            ),
          ),
        );
        ref.invalidate(walletProvider);
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isSavingUpi = false);
        String err = 'Failed to update UPI ID';
        if (e is DioException && e.response?.data is Map) {
          err = e.response?.data['message'] ?? err;
        }
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: AppColors.danger,
            behavior: SnackBarBehavior.floating,
            content: Text(err, style: GoogleFonts.plusJakartaSans(color: Colors.white)),
          ),
        );
      }
    }
  }

  void _requestPayout(double availableBalance, double minThreshold) async {
    final amount = double.tryParse(_amountController.text.trim()) ?? 0.0;

    if (amount <= 0) {
      setState(() {
        _isSuccess = false;
        _message = 'Please enter a valid withdrawal amount.';
      });
      return;
    }

    if (amount < minThreshold) {
      setState(() {
        _isSuccess = false;
        _message = 'The minimum withdrawal threshold is ₹${minThreshold.toStringAsFixed(0)}. Please enter at least ₹${minThreshold.toStringAsFixed(0)}.';
      });
      return;
    }

    if (amount > availableBalance) {
      setState(() {
        _isSuccess = false;
        _message = 'Requested amount exceeds your available balance of ₹${availableBalance.toStringAsFixed(2)}.';
      });
      return;
    }

    setState(() {
      _isSubmitting = true;
      _message = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final res = await api.post('/wallet/payout', data: {'amount': amount});

      if (mounted) {
        setState(() {
          _isSubmitting = false;
          _isSuccess = true;
          _message = res.data is Map && res.data['message'] != null
              ? res.data['message']
              : 'Payout request of ₹${amount.toStringAsFixed(2)} submitted successfully!';
          _amountController.clear();
          _selectedPresetAmount = null;
        });

        ref.invalidate(walletProvider);
        ref.invalidate(payoutRequestsProvider);
      }
    } catch (e) {
      if (mounted) {
        String errMsg = 'Failed to submit payout request.';
        if (e is DioException && e.response?.data is Map) {
          errMsg = e.response?.data['message'] ?? errMsg;
        }
        setState(() {
          _isSubmitting = false;
          _isSuccess = false;
          _message = errMsg;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final walletAsync = ref.watch(walletProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textPrimary),
          onPressed: () => context.pop(),
        ),
        title: Text(
          'Withdraw Funds',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: AppColors.textPrimary,
          ),
        ),
        actions: [
          BouncingCard(
            onTap: () => context.push('/withdraw/status'),
            child: Container(
              margin: const EdgeInsets.only(right: 16),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              decoration: BoxDecoration(
                color: AppColors.surfaceSecondary,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.history_rounded, size: 16, color: AppColors.primary),
                  const SizedBox(width: 4),
                  Text(
                    'History',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: walletAsync.when(
        data: (wallet) {
          if (_upiController.text.isEmpty && wallet.upiId != null) {
            _upiController.text = wallet.upiId!;
          }

          final minThreshold = wallet.minWithdrawalThreshold;
          final availableBalance = wallet.availableBalance;
          final isEligible = availableBalance >= minThreshold;

          // Preset amounts based on min threshold
          final presetList = <double>[
            minThreshold,
            minThreshold * 2,
            minThreshold * 4,
            availableBalance,
          ].where((amt) => amt > 0).toSet().toList();

          return SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 60),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Available Balance Hero Banner
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    gradient: AppColors.heroGradient,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: AppShadows.heroCard,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
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
                                'Withdrawable Balance',
                                style: GoogleFonts.plusJakartaSans(
                                  color: Colors.white.withValues(alpha: 0.85),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.18),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.flash_on_rounded, size: 13, color: Colors.amberAccent),
                                const SizedBox(width: 3),
                                Text(
                                  'Instant UPI',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                    color: Colors.white,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      AnimatedCounter(
                        value: availableBalance,
                        prefix: '₹',
                        style: GoogleFonts.plusJakartaSans(
                          color: Colors.white,
                          fontSize: 34,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.5,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              isEligible ? Icons.verified_rounded : Icons.info_outline_rounded,
                              size: 15,
                              color: isEligible ? const Color(0xFF00F0FF) : Colors.amberAccent,
                            ),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                isEligible
                                    ? '✓ Minimum threshold ₹${minThreshold.toStringAsFixed(0)} met. Ready for withdrawal.'
                                    : 'Minimum withdrawal threshold set by admin is ₹${minThreshold.toStringAsFixed(0)}. (Need ₹${(minThreshold - availableBalance).toStringAsFixed(0)} more)',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 11,
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(duration: 350.ms).slideY(begin: 0.06, end: 0),

                const SizedBox(height: 20),

                // Fast Track Status Tracker Button
                BouncingCard(
                  onTap: () => context.push('/withdraw/status'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(color: AppColors.border),
                      boxShadow: AppShadows.soft,
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: AppColors.skyBg,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.receipt_long_rounded, color: AppColors.sky, size: 20),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Track Withdrawal Status',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.textPrimary,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                'Check live settlement status, UTR numbers & history',
                                style: GoogleFonts.plusJakartaSans(
                                  fontSize: 11,
                                  color: AppColors.textMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Icon(Icons.chevron_right_rounded, color: AppColors.textMuted, size: 20),
                      ],
                    ),
                  ),
                ).animate().fadeIn(delay: 50.ms, duration: 300.ms),

                const SizedBox(height: 20),

                // UPI Destination Card
                Text(
                  'UPI Destination',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),

                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: AppColors.border),
                    boxShadow: AppShadows.soft,
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.mintBg,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: AppColors.mint.withValues(alpha: 0.2),
                                width: 1,
                              ),
                            ),
                            child: const Icon(
                              Icons.account_balance_rounded,
                              color: AppColors.mint,
                              size: 22,
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  wallet.upiId?.isNotEmpty == true ? wallet.upiId! : 'No UPI Configured',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Row(
                                  children: [
                                    Icon(
                                      wallet.upiId?.isNotEmpty == true ? Icons.verified_rounded : Icons.info_outline_rounded,
                                      size: 13,
                                      color: wallet.upiId?.isNotEmpty == true ? AppColors.mint : AppColors.amber,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      wallet.upiId?.isNotEmpty == true
                                          ? 'Verified for Instant Transfer'
                                          : 'Save valid UPI ID below',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 11,
                                        color: wallet.upiId?.isNotEmpty == true ? AppColors.mint : AppColors.amber,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          BouncingCard(
                            onTap: _isSavingUpi ? null : _saveUpi,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                              decoration: BoxDecoration(
                                color: AppColors.primaryLight,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: _isSavingUpi
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                                    )
                                  : Text(
                                      'Save',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.primary,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),
                      TextField(
                        controller: _upiController,
                        style: GoogleFonts.plusJakartaSans(fontSize: 14),
                        decoration: InputDecoration(
                          hintText: 'Enter UPI ID (e.g. mobile@upi or name@okaxis)',
                          prefixIcon: const Icon(Icons.alternate_email_rounded, size: 18, color: AppColors.textMuted),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: AppColors.border),
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 100.ms, duration: 300.ms),

                const SizedBox(height: 22),

                // Request Withdrawal Form Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(color: AppColors.border),
                    boxShadow: AppShadows.soft,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Enter Amount',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: AppColors.textPrimary,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppColors.mintBg,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              '0% Transfer Fee',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: AppColors.mint,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Quick preset amount pills
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        physics: const BouncingScrollPhysics(),
                        child: Row(
                          children: presetList.map((amt) {
                            final isSelected = _selectedPresetAmount == amt;
                            final isMax = amt == availableBalance;
                            final label = isMax ? 'Max (₹${amt.toStringAsFixed(0)})' : '₹${amt.toStringAsFixed(0)}';

                            return Padding(
                              padding: const EdgeInsets.only(right: 8.0),
                              child: BouncingCard(
                                onTap: () {
                                  setState(() {
                                    _selectedPresetAmount = amt;
                                    _amountController.text = amt.toStringAsFixed(0);
                                  });
                                },
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: isSelected ? AppColors.primary : AppColors.surfaceSecondary,
                                    borderRadius: BorderRadius.circular(16),
                                    border: Border.all(
                                      color: isSelected ? AppColors.primary : AppColors.border,
                                    ),
                                  ),
                                  child: Text(
                                    label,
                                    style: GoogleFonts.plusJakartaSans(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                      color: isSelected ? Colors.white : AppColors.textPrimary,
                                    ),
                                  ),
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                      ),

                      const SizedBox(height: 16),

                      TextField(
                        controller: _amountController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.bold),
                        decoration: InputDecoration(
                          hintText: 'Min ₹${minThreshold.toStringAsFixed(0)}',
                          prefixIcon: const Icon(Icons.currency_rupee_rounded, size: 20, color: AppColors.primary),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          suffixIcon: TextButton(
                            onPressed: () {
                              setState(() {
                                _amountController.text = availableBalance.toStringAsFixed(0);
                                _selectedPresetAmount = availableBalance;
                              });
                            },
                            child: Text(
                              'MAX',
                              style: GoogleFonts.plusJakartaSans(
                                fontWeight: FontWeight.w800,
                                fontSize: 12,
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Status or Error Alert Message
                      if (_message != null) ...[
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 300),
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: _isSuccess ? AppColors.mintBg : AppColors.dangerBg,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: (_isSuccess ? AppColors.mint : AppColors.danger).withValues(alpha: 0.3),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Icon(
                                    _isSuccess ? Icons.check_circle_rounded : Icons.error_outline_rounded,
                                    color: _isSuccess ? AppColors.mint : AppColors.danger,
                                    size: 18,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      _message!,
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                        color: _isSuccess ? AppColors.mint : AppColors.danger,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              if (_isSuccess) ...[
                                const SizedBox(height: 10),
                                BouncingCard(
                                  onTap: () => context.push('/withdraw/status'),
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                    decoration: BoxDecoration(
                                      color: AppColors.mint,
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(
                                          'View in Tracking Status',
                                          style: GoogleFonts.plusJakartaSans(
                                            color: Colors.white,
                                            fontWeight: FontWeight.w700,
                                            fontSize: 11,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 14),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // Submit Withdrawal Button
                      BouncingCard(
                        onTap: _isSubmitting
                            ? null
                            : () => _requestPayout(availableBalance, minThreshold),
                        child: Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          decoration: BoxDecoration(
                            gradient: isEligible ? AppColors.primaryGradient : null,
                            color: !isEligible ? AppColors.border : null,
                            borderRadius: BorderRadius.circular(18),
                            boxShadow: isEligible
                                ? [
                                    BoxShadow(
                                      color: AppColors.primary.withValues(alpha: 0.3),
                                      blurRadius: 12,
                                      offset: const Offset(0, 4),
                                    ),
                                  ]
                                : null,
                          ),
                          alignment: Alignment.center,
                          child: _isSubmitting
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                                )
                              : Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                                    const SizedBox(width: 8),
                                    Text(
                                      'Submit Withdrawal Request',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.white,
                                      ),
                                    ),
                                  ],
                                ),
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 150.ms, duration: 300.ms),
              ],
            ),
          );
        },
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: Column(
            children: [
              SkeletonLoader(height: 160, borderRadius: 24),
              SizedBox(height: 16),
              SkeletonLoader(height: 120, borderRadius: 20),
              SizedBox(height: 16),
              SkeletonLoader(height: 220, borderRadius: 22),
            ],
          ),
        ),
        error: (e, _) => Center(
          child: Text(
            'Failed to load wallet information',
            style: GoogleFonts.plusJakartaSans(color: AppColors.danger),
          ),
        ),
      ),
    );
  }
}
