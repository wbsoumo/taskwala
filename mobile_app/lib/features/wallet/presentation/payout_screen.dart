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
      if (res.data['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('UPI ID updated successfully')),
        );
        ref.refresh(walletProvider);
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
        ref.refresh(walletProvider);
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
        title: const Text('Withdraw Funds'),
      ),
      body: walletAsync.when(
        data: (wallet) {
          if (_upiController.text.isEmpty && wallet.upiId != null) {
            _upiController.text = wallet.upiId!;
          }

          return SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Available Balance Card
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Withdrawable Balance', style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary)),
                          const SizedBox(height: 4),
                          Text('₹${wallet.availableBalance.toStringAsFixed(2)}',
                              style: GoogleFonts.inter(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.success)),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(color: AppColors.success.withAlpha(20), shape: BoxShape.circle),
                        child: const Icon(Icons.account_balance_wallet, color: AppColors.success),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),

                // UPI Management Section
                Text('UPI Payment Destination', style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _upiController,
                        decoration: const InputDecoration(
                          hintText: 'Enter UPI ID (e.g. name@upi)',
                          prefixIcon: Icon(Icons.payment),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton(
                      onPressed: _saveUpi,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      ),
                      child: const Text('Save'),
                    ),
                  ],
                ),

                const SizedBox(height: 28),

                // Withdrawal Form
                Text('Request Payout', style: GoogleFonts.inter(fontSize: 15, fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),

                TextField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Withdrawal Amount (₹)',
                    prefixIcon: Icon(Icons.currency_rupee),
                    helperText: 'Minimum withdrawal: ₹1.00',
                  ),
                ),

                const SizedBox(height: 20),

                if (_message != null) ...[
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: (_isSuccess ? AppColors.success : AppColors.danger).withAlpha(20),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: (_isSuccess ? AppColors.success : AppColors.danger).withAlpha(60)),
                    ),
                    child: Text(
                      _message!,
                      style: GoogleFonts.inter(color: _isSuccess ? AppColors.success : AppColors.danger, fontSize: 13),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],

                ElevatedButton(
                  onPressed: _isSubmitting || wallet.availableBalance <= 0 ? null : _requestPayout,
                  child: _isSubmitting
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Confirm Withdrawal Request'),
                ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (err, _) => Center(child: Text('Error: $err')),
      ),
    );
  }
}
