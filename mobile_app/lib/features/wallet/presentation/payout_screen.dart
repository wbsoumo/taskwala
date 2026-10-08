import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../core/theme/app_theme.dart';

class PayoutScreen extends ConsumerStatefulWidget {
  const PayoutScreen({super.key});

  @override
  ConsumerState<PayoutScreen> createState() => _PayoutScreenState();
}

class _PayoutScreenState extends ConsumerState<PayoutScreen> {
  final _amountController = TextEditingController();
  final _upiController = TextEditingController();
  bool _isSubmitting = false;
  String? _message;
  bool _isSuccess = false;

  @override
  void dispose() {
    _amountController.dispose();
    _upiController.dispose();
    super.dispose();
  }

  void _saveUpi() async {
    final upi = _upiController.text.trim();
    if (upi.isEmpty) return;

    final api = ref.read(apiClientProvider);
    try {
      final res = await api.post('/wallet/upi', data: {'upi_id': upi});
      if (res.data['success'] == true && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('UPI ID updated successfully')),
        );
        ref.invalidate(walletProvider);
      }
    } catch (_) {}
  }

  void _requestPayout() async {
    final amount = double.tryParse(_amountController.text) ?? 0.0;
    if (amount <= 0) return;

    setState(() {
      _isSubmitting = true;
      _message = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final res = await api.post('/wallet/payout', data: {'amount': amount});

      if (res.data['success'] == true) {
        setState(() {
          _isSubmitting = false;
          _isSuccess = true;
          _message = 'Payout request of ₹${amount.toStringAsFixed(0)} submitted successfully!';
        });
        ref.invalidate(walletProvider);
      } else {
        setState(() {
          _isSubmitting = false;
          _isSuccess = false;
          _message = res.data['message'] ?? 'Failed to submit payout request';
        });
      }
    } catch (e) {
      setState(() {
        _isSubmitting = false;
        _isSuccess = false;
        _message = 'Network error during payout request';
      });
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
        title: Text('UPI & Payouts', style: GoogleFonts.inter(fontWeight: FontWeight.bold, fontSize: 18)),
      ),
      body: walletAsync.when(
        data: (wallet) {
          if (_upiController.text.isEmpty && wallet.upiId != null) {
            _upiController.text = wallet.upiId!;
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Saved UPI Card matching reference UI
                Text('UPI Details', style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),

                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: AppColors.successBg,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(Icons.account_balance_wallet_rounded, color: AppColors.success, size: 22),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  wallet.upiId ?? 'soumojit@upi',
                                  style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 2),
                                Row(
                                  children: [
                                    const Icon(Icons.check_circle, size: 12, color: AppColors.success),
                                    const SizedBox(width: 4),
                                    Text('Verified', style: GoogleFonts.inter(fontSize: 11, color: AppColors.success, fontWeight: FontWeight.bold)),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.edit_outlined, size: 20, color: AppColors.textSecondary),
                            onPressed: _saveUpi,
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _upiController,
                        decoration: InputDecoration(
                          hintText: 'Enter UPI ID (e.g. name@upi)',
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border)),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Payout Settings & Request Card matching reference UI
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Payout Settings', style: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      Text('Minimum withdrawal amount: ₹500', style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
                      const SizedBox(height: 16),
                      TextField(
                        controller: _amountController,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          hintText: 'Enter amount to withdraw',
                          prefixIcon: const Icon(Icons.currency_rupee, size: 18),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (_message != null) ...[
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _isSuccess ? AppColors.successBg : AppColors.dangerBg,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(_message!, style: GoogleFonts.inter(color: _isSuccess ? AppColors.success : AppColors.danger, fontSize: 12)),
                        ),
                        const SizedBox(height: 12),
                      ],
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton(
                          onPressed: _isSubmitting || wallet.availableBalance <= 0 ? null : _requestPayout,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: _isSubmitting
                              ? const CircularProgressIndicator(color: Colors.white)
                              : Text('Request Withdrawal', style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // Payout History List matching reference UI
                Text('Payout History', style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                const SizedBox(height: 12),

                _buildPayoutHistoryItem('₹1,500', '10 Oct 2026', 'Paid', AppColors.success, AppColors.successBg),
                _buildPayoutHistoryItem('₹500', '01 Oct 2026', 'Processing', AppColors.pending, AppColors.pendingBg),
                _buildPayoutHistoryItem('₹800', '15 Sep 2026', 'Paid', AppColors.success, AppColors.successBg),
                _buildPayoutHistoryItem('₹600', '01 Sep 2026', 'Failed', AppColors.danger, AppColors.dangerBg),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('Error: $err')),
      ),
    );
  }

  Widget _buildPayoutHistoryItem(String amount, String date, String status, Color statusColor, Color statusBg) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              shape: BoxShape.circle,
            ),
            alignment: Alignment.center,
            child: Text('₹', style: GoogleFonts.inter(fontWeight: FontWeight.bold, color: AppColors.primary)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(amount, style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                Text(date, style: GoogleFonts.inter(fontSize: 11, color: AppColors.textMuted)),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: statusBg,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              status,
              style: GoogleFonts.inter(fontSize: 11, fontWeight: FontWeight.bold, color: statusColor),
            ),
          ),
        ],
      ),
    );
  }
}
