import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:share_plus/share_plus.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/models/models.dart';
import '../../../core/theme/app_theme.dart';

class LinkGeneratorScreen extends ConsumerStatefulWidget {
  final Campaign campaign;

  const LinkGeneratorScreen({super.key, required this.campaign});

  @override
  ConsumerState<LinkGeneratorScreen> createState() => _LinkGeneratorScreenState();
}

class _LinkGeneratorScreenState extends ConsumerState<LinkGeneratorScreen> {
  late TextEditingController _customerPayoutController;
  bool _isGenerating = false;
  AffiliateLink? _generatedLink;
  String? _error;

  @override
  void initState() {
    super.initState();
    _customerPayoutController = TextEditingController(
      text: (widget.campaign.affiliatePayout * 0.5).toStringAsFixed(0),
    );
  }

  @override
  void dispose() {
    _customerPayoutController.dispose();
    super.dispose();
  }

  double get _customerPayout => double.tryParse(_customerPayoutController.text) ?? 0.0;
  double get _affiliateCommission => (widget.campaign.affiliatePayout - _customerPayout).clamp(0.0, widget.campaign.affiliatePayout);

  void _generateLink() async {
    setState(() {
      _isGenerating = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/links/generate', data: {
        'campaign_id': widget.campaign.id,
        'customer_payout': _customerPayout,
      });

      if (response.data['success'] == true) {
        setState(() {
          _generatedLink = AffiliateLink.fromJson(response.data['data']);
          _isGenerating = false;
        });
        ref.refresh(myLinksProvider);
      } else {
        setState(() {
          _error = response.data['message'] ?? 'Failed to generate link';
          _isGenerating = false;
        });
      }
    } catch (e) {
      setState(() {
        _error = 'Error connecting to backend server';
        _isGenerating = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Generate Secure Link'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Campaign Banner / Summary
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 24,
                    backgroundColor: AppColors.primaryLight,
                    child: Text(
                      widget.campaign.name[0],
                      style: GoogleFonts.inter(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.campaign.name,
                          style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          widget.campaign.category,
                          style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Allowed Payout: ₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                          style: GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            if (_generatedLink == null) ...[
              // Payout Configurator
              Text(
                'Configure Customer Payout',
                style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(
                'Set how much payout to offer to your customer from your total allowed payout.',
                style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
              ),
              const SizedBox(height: 16),

              TextField(
                controller: _customerPayoutController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'Customer Payout (₹)',
                  prefixIcon: const Icon(Icons.currency_rupee),
                  helperText: 'Max allowed: ₹${widget.campaign.affiliatePayout.toStringAsFixed(0)}',
                ),
                onChanged: (_) => setState(() {}),
              ),
              const SizedBox(height: 20),

              // Live Preview Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight.withAlpha(50),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.primary.withAlpha(60)),
                ),
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Customer Gets:', style: GoogleFonts.inter(fontSize: 13, color: AppColors.textSecondary)),
                        Text('₹${_customerPayout.toStringAsFixed(0)}',
                            style: GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.success)),
                      ],
                    ),
                    const Divider(height: 20),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Your Commission:', style: GoogleFonts.inter(fontSize: 13, color: AppColors.textSecondary)),
                        Text('₹${_affiliateCommission.toStringAsFixed(0)}',
                            style: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primary)),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              if (_error != null) ...[
                Text(_error!, style: GoogleFonts.inter(color: AppColors.danger)),
                const SizedBox(height: 12),
              ],

              ElevatedButton(
                onPressed: _isGenerating ? null : _generateLink,
                child: _isGenerating
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('Generate Secure Affiliate Link'),
              ),
            ] else ...[
              // Link Generated Success State
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.success.withAlpha(100)),
                ),
                child: Column(
                  children: [
                    const Icon(Icons.check_circle_outline, color: AppColors.success, size: 56),
                    const SizedBox(height: 12),
                    Text(
                      'Secure Link Generated!',
                      style: GoogleFonts.inter(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Share this link with your customers to track clicks and conversions.',
                      textAlign: TextAlign.center,
                      style: GoogleFonts.inter(fontSize: 12, color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 20),

                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              _generatedLink!.secureUrl,
                              style: GoogleFonts.inter(fontSize: 13, fontWeight: FontWeight.w500),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.copy, size: 20),
                            onPressed: () {
                              Clipboard.setData(ClipboardData(text: _generatedLink!.secureUrl));
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Affiliate link copied!')),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Share.share('Check out this offer: ${_generatedLink!.secureUrl}');
                            },
                            icon: const Icon(Icons.share, size: 18),
                            label: const Text('Share Link'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => context.go('/links'),
                            child: const Text('My Links'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
