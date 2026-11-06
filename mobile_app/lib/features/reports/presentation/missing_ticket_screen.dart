import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:dio/dio.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/models/models.dart';
import '../../../shared/providers/app_providers.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/widgets/bouncing_card.dart';
import '../../../shared/widgets/campaign_logo.dart';
import '../../../shared/widgets/empty_state.dart';

class MissingTicketScreen extends ConsumerStatefulWidget {
  final int? initialCampaignId;

  const MissingTicketScreen({super.key, this.initialCampaignId});

  @override
  ConsumerState<MissingTicketScreen> createState() => _MissingTicketScreenState();
}

class _MissingTicketScreenState extends ConsumerState<MissingTicketScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  // Form Controllers
  final _formKey = GlobalKey<FormState>();
  final _orderIdController = TextEditingController();
  final _notesController = TextEditingController();

  Campaign? _selectedCampaign;
  DateTime _taskDate = DateTime.now();
  File? _screenshotFile;
  bool _isSubmitting = false;

  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    _orderIdController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final picked = await _picker.pickImage(
        source: source,
        imageQuality: 85,
        maxWidth: 1600,
      );
      if (picked != null) {
        setState(() {
          _screenshotFile = File(picked.path);
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Failed to select image: $e'),
            backgroundColor: AppColors.danger,
          ),
        );
      }
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _taskDate,
      firstDate: DateTime.now().subtract(const Duration(days: 90)),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.primary,
              onPrimary: Colors.white,
              surface: AppColors.surface,
              onSurface: AppColors.textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() {
        _taskDate = picked;
      });
    }
  }

  Future<void> _submitTicket() async {
    if (!_formKey.currentState!.validate()) return;

    if (_selectedCampaign == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select the campaign for this missing conversion.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    if (_screenshotFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please upload screenshot proof of order/registration.'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);
    HapticFeedback.mediumImpact();

    try {
      final api = ref.read(apiClientProvider);

      final fileName = _screenshotFile!.path.split('/').last;
      final formData = FormData.fromMap({
        'campaign_id': _selectedCampaign!.id,
        'order_reference_id': _orderIdController.text.trim(),
        'task_date': DateFormat('yyyy-MM-dd').format(_taskDate),
        'notes': _notesController.text.trim(),
        'screenshot': await MultipartFile.fromFile(
          _screenshotFile!.path,
          filename: fileName,
        ),
      });

      final response = await api.post('/tickets', data: formData);
      final ticketData = response.data['ticket'];
      final publicId = ticketData?['public_id'] ?? 'Claim Ticket';

      if (!mounted) return;

      // Reset Form
      _orderIdController.clear();
      _notesController.clear();
      setState(() {
        _screenshotFile = null;
        _isSubmitting = false;
      });

      // Refresh list
      ref.invalidate(missingTicketsProvider);

      // Switch to My Claims tab
      _tabController.animateTo(1);

      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          backgroundColor: AppColors.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.success.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_rounded, color: AppColors.success, size: 28),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Claim Submitted!',
                  style: GoogleFonts.plusJakartaSans(
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                    color: AppColors.textPrimary,
                  ),
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Your missing conversion ticket #$publicId has been submitted successfully.',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.surfaceSecondary,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline_rounded, color: AppColors.primary, size: 18),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'Our verification team audits proofs daily. Approved claims credit straight into your wallet.',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () => Navigator.of(ctx).pop(),
              child: const Text('View Status'),
            ),
          ],
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Failed to submit ticket: ${e.toString()}'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  void _showCampaignPicker(List<Campaign> campaigns) {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      isScrollControlled: true,
      builder: (ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          maxChildSize: 0.85,
          minChildSize: 0.4,
          expand: false,
          builder: (context, scrollController) {
            return Column(
              children: [
                Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.symmetric(vertical: 12),
                  decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Select Campaign',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        '${campaigns.length} available',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),
                Expanded(
                  child: ListView.separated(
                    controller: scrollController,
                    padding: const EdgeInsets.all(16),
                    itemCount: campaigns.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final c = campaigns[index];
                      final isSelected = _selectedCampaign?.id == c.id;
                      return InkWell(
                        onTap: () {
                          setState(() => _selectedCampaign = c);
                          Navigator.pop(ctx);
                        },
                        borderRadius: BorderRadius.circular(14),
                        child: Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primary.withValues(alpha: 0.08)
                                : AppColors.surfaceSecondary,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isSelected ? AppColors.primary : AppColors.border,
                              width: isSelected ? 1.5 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              CampaignLogo(
                                logoUrl: c.logo,
                                name: c.name,
                                size: 40,
                                borderRadius: 10,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      c.name,
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 14,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textPrimary,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${c.category} • Payout: ₹${c.affiliatePayout.toStringAsFixed(0)}',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 11,
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              if (isSelected)
                                const Icon(Icons.check_circle_rounded,
                                    color: AppColors.primary, size: 20),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  void _showImageOptions() {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.photo_library_rounded, color: AppColors.primary),
                title: Text(
                  'Choose from Gallery',
                  style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600),
                ),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.gallery);
                },
              ),
              ListTile(
                leading: const Icon(Icons.camera_alt_rounded, color: AppColors.primary),
                title: Text(
                  'Take Photo with Camera',
                  style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w600),
                ),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(ImageSource.camera);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _viewFullProof(String url) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(16),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            InteractiveViewer(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Image.network(
                  url,
                  fit: BoxFit.contain,
                  loadingBuilder: (c, child, progress) {
                    if (progress == null) return child;
                    return Container(
                      height: 300,
                      color: AppColors.surface,
                      child: const Center(child: CircularProgressIndicator()),
                    );
                  },
                  errorBuilder: (_, __, ___) => Container(
                    padding: const EdgeInsets.all(24),
                    color: AppColors.surface,
                    child: Text(
                      'Failed to load proof image',
                      style: GoogleFonts.plusJakartaSans(color: AppColors.textPrimary),
                    ),
                  ),
                ),
              ),
            ),
            IconButton(
              icon: const CircleAvatar(
                backgroundColor: Colors.black54,
                child: Icon(Icons.close_rounded, color: Colors.white, size: 20),
              ),
              onPressed: () => Navigator.pop(ctx),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final campaignsAsync = ref.watch(campaignsProvider);
    final ticketsAsync = ref.watch(missingTicketsProvider);

    // Auto-select initial campaign if passed
    if (widget.initialCampaignId != null && _selectedCampaign == null) {
      campaignsAsync.whenData((cList) {
        final match = cList.where((c) => c.id == widget.initialCampaignId).firstOrNull;
        if (match != null && mounted) {
          setState(() => _selectedCampaign = match);
        }
      });
    }

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Text(
          'Missing Lead Claims',
          style: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w800,
            fontSize: 18,
            color: AppColors.textPrimary,
          ),
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh_rounded, color: AppColors.textPrimary),
            onPressed: () {
              ref.invalidate(missingTicketsProvider);
              HapticFeedback.lightImpact();
            },
          ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textSecondary,
          indicatorColor: AppColors.primary,
          indicatorWeight: 3,
          labelStyle: GoogleFonts.plusJakartaSans(
            fontWeight: FontWeight.w700,
            fontSize: 13,
          ),
          tabs: const [
            Tab(
              icon: Icon(Icons.add_task_rounded, size: 18),
              text: 'Submit Claim',
            ),
            Tab(
              icon: Icon(Icons.history_rounded, size: 18),
              text: 'My Claims & Proofs',
            ),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // TAB 1: SUBMIT CLAIM FORM
          _buildSubmitTab(campaignsAsync),

          // TAB 2: MY TICKETS LIST
          _buildTicketsTab(ticketsAsync),
        ],
      ),
    );
  }

  Widget _buildSubmitTab(AsyncValue<List<Campaign>> campaignsAsync) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Pro Tip / Assurance Banner
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF2563EB), Color(0xFF1D4ED8)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF2563EB).withValues(alpha: 0.25),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.verified_user_rounded, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '100% Guaranteed Lead Auditing',
                          style: GoogleFonts.plusJakartaSans(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Completed a task but the lead did not reflect? Submit your order/customer ID and proof. We cross-verify with the advertiser and credit your wallet.',
                          style: GoogleFonts.plusJakartaSans(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontSize: 11.5,
                            height: 1.35,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Field 1: Campaign Selector
            Text(
              'Select Campaign *',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            campaignsAsync.when(
              data: (campaigns) {
                return InkWell(
                  onTap: () => _showCampaignPicker(campaigns),
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: _selectedCampaign != null ? AppColors.primary : AppColors.border,
                      ),
                    ),
                    child: Row(
                      children: [
                        if (_selectedCampaign != null) ...[
                          CampaignLogo(
                            logoUrl: _selectedCampaign!.logo,
                            name: _selectedCampaign!.name,
                            size: 32,
                            borderRadius: 8,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedCampaign!.name,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 13,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                Text(
                                  'Payout: ₹${_selectedCampaign!.affiliatePayout.toStringAsFixed(0)}',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 11,
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ] else ...[
                          const Icon(Icons.campaign_outlined, color: AppColors.textMuted, size: 20),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Choose Campaign',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                color: AppColors.textMuted,
                              ),
                            ),
                          ),
                        ],
                        const Icon(Icons.arrow_drop_down_rounded, color: AppColors.textSecondary),
                      ],
                    ),
                  ),
                );
              },
              loading: () => Container(
                height: 48,
                decoration: BoxDecoration(
                  color: AppColors.surfaceSecondary,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Center(child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))),
              ),
              error: (_, __) => Text(
                'Failed to load campaigns',
                style: GoogleFonts.plusJakartaSans(color: AppColors.danger),
              ),
            ),
            const SizedBox(height: 16),

            // Field 2: Order / Reference ID
            Text(
              'Order / Reference ID *',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            TextFormField(
              controller: _orderIdController,
              decoration: InputDecoration(
                hintText: 'e.g. ORD-98214, Application #, Mobile or Account No',
                hintStyle: GoogleFonts.plusJakartaSans(fontSize: 12.5, color: AppColors.textMuted),
                prefixIcon: const Icon(Icons.numbers_rounded, size: 18, color: AppColors.textMuted),
                filled: true,
                fillColor: AppColors.surface,
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Please enter Order / Reference ID' : null,
            ),
            const SizedBox(height: 16),

            // Field 3: Task Date Picker
            Text(
              'Date Task Completed *',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            InkWell(
              onTap: _pickDate,
              borderRadius: BorderRadius.circular(14),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.calendar_today_rounded, size: 18, color: AppColors.textSecondary),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        DateFormat('dd MMMM yyyy').format(_taskDate),
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    const Icon(Icons.edit_calendar_rounded, size: 18, color: AppColors.primary),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Field 4: Screenshot Proof Upload
            Text(
              'Proof Screenshot * (Order confirmation / Status page)',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            if (_screenshotFile == null)
              InkWell(
                onTap: _showImageOptions,
                borderRadius: BorderRadius.circular(16),
                child: Container(
                  height: 130,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border, style: BorderStyle.solid),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.08),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.add_photo_alternate_rounded,
                            size: 28, color: AppColors.primary),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Tap to Upload Screenshot Proof',
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                          color: AppColors.primary,
                        ),
                      ),
                      Text(
                        'PNG, JPG, JPEG up to 10MB',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          color: AppColors.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
              )
            else
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.primary),
                ),
                child: Row(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: Image.file(
                        _screenshotFile!,
                        width: 70,
                        height: 70,
                        fit: BoxFit.cover,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.check_circle_rounded,
                                  color: AppColors.success, size: 16),
                              const SizedBox(width: 4),
                              Text(
                                'Proof Attached',
                                style: GoogleFonts.plusJakartaSans(
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13,
                                  color: AppColors.success,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _screenshotFile!.path.split('/').last,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 11,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              InkWell(
                                onTap: _showImageOptions,
                                child: Text(
                                  'Change',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              InkWell(
                                onTap: () => setState(() => _screenshotFile = null),
                                child: Text(
                                  'Remove',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.danger,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            const SizedBox(height: 16),

            // Field 5: Notes / Description
            Text(
              'Additional Details / Notes (Optional)',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w700,
                fontSize: 13,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            TextFormField(
              controller: _notesController,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'Add details (e.g. Registered with email, completed KYC on this date, etc.)',
                hintStyle: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textMuted),
                filled: true,
                fillColor: AppColors.surface,
                contentPadding: const EdgeInsets.all(14),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  elevation: 2,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: _isSubmitting ? null : _submitTicket,
                child: _isSubmitting
                    ? const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                          ),
                          SizedBox(width: 12),
                          Text('Submitting Claim...', style: TextStyle(fontWeight: FontWeight.w700)),
                        ],
                      )
                    : Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.send_rounded, size: 18),
                          const SizedBox(width: 8),
                          Text(
                            'Submit Missing Lead Claim',
                            style: GoogleFonts.plusJakartaSans(
                              fontWeight: FontWeight.w800,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildTicketsTab(AsyncValue<List<MissingConversionTicket>> ticketsAsync) {
    return ticketsAsync.when(
      data: (tickets) {
        if (tickets.isEmpty) {
          return TaskwalaEmptyState(
            icon: Icons.assignment_turned_in_outlined,
            title: 'No Claims Submitted Yet',
            message: 'All your tracked leads are up to date! If any lead is missing, submit a claim using the first tab.',
            actionLabel: 'Submit A Claim',
            onAction: () => _tabController.animateTo(0),
          );
        }

        return RefreshIndicator(
          onRefresh: () async => ref.refresh(missingTicketsProvider.future),
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: tickets.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final ticket = tickets[index];
              return _buildTicketCard(ticket);
            },
          ),
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (e, _) => Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 40),
            const SizedBox(height: 10),
            Text(
              'Failed to load tickets',
              style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            ElevatedButton(
              onPressed: () => ref.invalidate(missingTicketsProvider),
              child: const Text('Try Again'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTicketCard(MissingConversionTicket ticket) {
    Color statusBg;
    Color statusText;
    IconData statusIcon;
    String statusLabel;

    switch (ticket.status.toLowerCase()) {
      case 'approved':
        statusBg = const Color(0xFF10B981).withValues(alpha: 0.12);
        statusText = const Color(0xFF059669);
        statusIcon = Icons.check_circle_rounded;
        statusLabel = 'Approved (+₹${ticket.approvedPayout.toStringAsFixed(0)})';
        break;
      case 'rejected':
        statusBg = const Color(0xFFEF4444).withValues(alpha: 0.12);
        statusText = const Color(0xFFDC2626);
        statusIcon = Icons.cancel_rounded;
        statusLabel = 'Rejected';
        break;
      case 'under_review':
        statusBg = const Color(0xFF3B82F6).withValues(alpha: 0.12);
        statusText = const Color(0xFF2563EB);
        statusIcon = Icons.policy_rounded;
        statusLabel = 'Under Review';
        break;
      case 'pending':
      default:
        statusBg = const Color(0xFFF59E0B).withValues(alpha: 0.12);
        statusText = const Color(0xFFD97706);
        statusIcon = Icons.hourglass_top_rounded;
        statusLabel = 'Pending Review';
        break;
    }

    return BouncingCard(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Row: Campaign + Status Badge
            Row(
              children: [
                CampaignLogo(
                  logoUrl: ticket.campaignLogo,
                  name: ticket.campaignName,
                  size: 38,
                  borderRadius: 10,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        ticket.campaignName,
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w800,
                          fontSize: 14,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      Text(
                        'Task Date: ${ticket.taskDate}',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: statusBg,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(statusIcon, color: statusText, size: 14),
                      const SizedBox(width: 4),
                      Text(
                        statusLabel,
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                          fontSize: 11,
                          color: statusText,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            const Divider(height: 1),
            const SizedBox(height: 12),

            // Order ID & Ticket ID Row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'REFERENCE / ORDER ID',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 9.5,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMuted,
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      ticket.orderReferenceId ?? 'N/A',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'TICKET ID',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 9.5,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textMuted,
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '#${ticket.publicId}',
                      style: GoogleFonts.plusJakartaSans(
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                        color: AppColors.primary,
                      ),
                    ),
                  ],
                ),
              ],
            ),

            if (ticket.notes != null && ticket.notes!.isNotEmpty) ...[
              const SizedBox(height: 10),
              Text(
                'Notes: ${ticket.notes}',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 11.5,
                  color: AppColors.textSecondary,
                ),
              ),
            ],

            // Admin response notes
            if (ticket.adminNotes != null && ticket.adminNotes!.isNotEmpty) ...[
              const SizedBox(height: 10),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: statusBg.withValues(alpha: 0.5),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: statusText.withValues(alpha: 0.3)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(Icons.rate_review_outlined, size: 14, color: statusText),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Admin: ${ticket.adminNotes}',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                          color: statusText,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            const SizedBox(height: 12),
            // Proof Thumbnail & View action
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  ticket.createdAt.isNotEmpty
                      ? 'Filed ${ticket.createdAt.split('T').first}'
                      : '',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 10.5,
                    color: AppColors.textMuted,
                  ),
                ),
                if (ticket.screenshotUrl.isNotEmpty)
                  InkWell(
                    onTap: () => _viewFullProof(ticket.screenshotUrl),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.image_outlined, size: 14, color: AppColors.primary),
                        const SizedBox(width: 4),
                        Text(
                          'View Proof Screenshot',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primary,
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
