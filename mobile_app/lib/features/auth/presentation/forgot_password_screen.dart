import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:dio/dio.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/widgets/bouncing_card.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKeyStep1 = GlobalKey<FormState>();
  final _formKeyStep2 = GlobalKey<FormState>();

  final _contactController = TextEditingController();
  final _otpController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  int _lookupTab = 0; // 0 = Email, 1 = Name, 2 = Mobile
  bool _isSubmitting = false;
  bool _obscureNewPass = true;
  bool _obscureConfirmPass = true;
  String? _errorMessage;

  // Step 2 state
  int _currentStep = 1;
  String? _emailHint;
  String? _debugCode;

  // Resend Timer
  Timer? _resendTimer;
  int _resendSeconds = 0;

  @override
  void dispose() {
    _resendTimer?.cancel();
    _contactController.dispose();
    _otpController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  void _startResendTimer() {
    _resendTimer?.cancel();
    setState(() => _resendSeconds = 60);
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendSeconds <= 1) {
        timer.cancel();
        setState(() => _resendSeconds = 0);
      } else {
        setState(() => _resendSeconds--);
      }
    });
  }

  Future<void> _handleSendOtp() async {
    if (!_formKeyStep1.currentState!.validate()) return;
    FocusScope.of(context).unfocus();

    setState(() {
      _isSubmitting = true;
      _errorMessage = null;
    });

    final login = _contactController.text.trim();

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/auth/forgot-password', data: {
        'login': login,
      });

      final data = response.data;
      if (response.statusCode == 200 && data != null && data['success'] == true) {
        setState(() {
          _currentStep = 2;
          _emailHint = data['email_hint']?.toString();
          _debugCode = data['debug_code']?.toString();
          _isSubmitting = false;
          _errorMessage = null;
        });

        if (_debugCode != null && _debugCode!.isNotEmpty) {
          _otpController.text = _debugCode!;
        }

        _startResendTimer();
      } else {
        setState(() {
          _isSubmitting = false;
          _errorMessage = data?['message'] ?? 'Failed to send verification code. Please check your contact.';
        });
      }
    } on DioException catch (e) {
      String msg = 'Failed to send verification code.';
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        msg = resData['message']?.toString() ??
            (resData['errors'] is Map ? (resData['errors'] as Map).values.first?.toString() : null) ??
            msg;
      }
      setState(() {
        _isSubmitting = false;
        _errorMessage = msg;
      });
    } catch (e) {
      setState(() {
        _isSubmitting = false;
        _errorMessage = 'Connection error. Please try again.';
      });
    }
  }

  Future<void> _handleResetPassword() async {
    if (!_formKeyStep2.currentState!.validate()) return;
    FocusScope.of(context).unfocus();

    setState(() {
      _isSubmitting = true;
      _errorMessage = null;
    });

    final login = _contactController.text.trim();
    final otp = _otpController.text.trim();
    final newPass = _newPasswordController.text;
    final confirmPass = _confirmPasswordController.text;

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.post('/auth/reset-password', data: {
        'login': login,
        'otp': otp,
        'password': newPass,
        'password_confirmation': confirmPass,
      });

      final data = response.data;
      if (response.statusCode == 200 && data != null && data['success'] == true) {
        setState(() {
          _isSubmitting = false;
        });

        // Show success sheet and pop back to login
        if (mounted) {
          _showSuccessDialog();
        }
      } else {
        setState(() {
          _isSubmitting = false;
          _errorMessage = data?['message'] ?? 'Failed to reset password. Please verify your OTP.';
        });
      }
    } on DioException catch (e) {
      String msg = 'Failed to reset password.';
      if (e.response?.data is Map) {
        final resData = e.response!.data as Map;
        msg = resData['message']?.toString() ??
            (resData['errors'] is Map ? (resData['errors'] as Map).values.first?.toString() : null) ??
            msg;
      }
      setState(() {
        _isSubmitting = false;
        _errorMessage = msg;
      });
    } catch (e) {
      setState(() {
        _isSubmitting = false;
        _errorMessage = 'Connection error. Please try again.';
      });
    }
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        backgroundColor: AppColors.surface,
        contentPadding: const EdgeInsets.all(24),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: AppColors.mintBg,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle_rounded, color: AppColors.mint, size: 40),
            ).animate().scale(duration: 400.ms, curve: Curves.easeOutBack),
            const SizedBox(height: 18),
            Text(
              'Password Updated!',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Your new password has been saved. Please sign in with your updated credentials.',
              textAlign: TextAlign.center,
              style: GoogleFonts.plusJakartaSans(
                fontSize: 13,
                color: AppColors.textSecondary,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: () {
                  Navigator.of(ctx).pop();
                  context.go('/login');
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                child: Text(
                  'Back to Sign In',
                  style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w700, fontSize: 14),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: AppColors.textPrimary),
          onPressed: () => context.pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Badge & Aura
              Center(
                child: Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: AppShadows.heroCard,
                  ),
                  child: const Icon(Icons.lock_reset_rounded, color: Colors.white, size: 32),
                ).animate().scale(duration: 400.ms, curve: Curves.easeOutBack),
              ),
              const SizedBox(height: 20),

              Center(
                child: Text(
                  _currentStep == 1 ? 'Forgot Password?' : 'Create New Password',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                    letterSpacing: -0.4,
                  ),
                ),
              ),
              const SizedBox(height: 8),

              Center(
                child: Text(
                  _currentStep == 1
                      ? 'No worries! Enter your registered mobile or email to get a 6-digit reset code.'
                      : 'Enter the verification code sent to your contact and choose a strong password.',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                    height: 1.4,
                  ),
                ),
              ),
              const SizedBox(height: 28),

              // Main Auth Card
              Container(
                padding: const EdgeInsets.all(22),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(24),
                  border: Border.all(color: AppColors.border),
                  boxShadow: AppShadows.soft,
                ),
                child: _currentStep == 1 ? _buildStep1Form() : _buildStep2Form(),
              ).animate().fadeIn(duration: 400.ms),

              const SizedBox(height: 24),

              // Back to Login link
              Center(
                child: TextButton(
                  onPressed: () => context.pop(),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.arrow_back_rounded, size: 16, color: AppColors.textSecondary),
                      const SizedBox(width: 6),
                      Text(
                        'Remembered your password? Sign In',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.primary,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStep1Form() {
    return Form(
      key: _formKeyStep1,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Toggle Tabs (Email, Name, Mobile)
          Container(
            padding: const EdgeInsets.all(4),
            decoration: BoxDecoration(
              color: AppColors.surfaceSecondary,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                _buildLookupTab(0, 'Email'),
                _buildLookupTab(1, 'Name'),
                _buildLookupTab(2, 'Mobile'),
              ],
            ),
          ),
          const SizedBox(height: 18),

          TextFormField(
            controller: _contactController,
            keyboardType: _lookupTab == 2
                ? TextInputType.phone
                : (_lookupTab == 0 ? TextInputType.emailAddress : TextInputType.text),
            style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600),
            decoration: InputDecoration(
              hintText: _lookupTab == 0
                  ? 'Enter registered email address'
                  : (_lookupTab == 1 ? 'Enter your account name' : 'Enter 10-digit mobile number'),
              prefixIcon: _lookupTab == 2
                  ? Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      alignment: Alignment.centerLeft,
                      width: 65,
                      child: Text(
                        '+91',
                        style: GoogleFonts.plusJakartaSans(
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    )
                  : Icon(
                      _lookupTab == 0 ? Icons.email_outlined : Icons.person_outline_rounded,
                      size: 20,
                      color: AppColors.textMuted,
                    ),
            ),
            validator: (val) {
              if (val == null || val.trim().isEmpty) {
                return _lookupTab == 0
                    ? 'Please enter email address'
                    : (_lookupTab == 1 ? 'Please enter your name' : 'Please enter mobile number');
              }
              if (_lookupTab == 2 && val.trim().length < 10) {
                return 'Please enter a valid 10-digit mobile number';
              }
              if (_lookupTab == 0 && !val.contains('@')) {
                return 'Please enter a valid email address';
              }
              return null;
            },
          ),
          const SizedBox(height: 10),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.info_outline_rounded, size: 14, color: AppColors.primary),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'The 6-digit verification code will always be sent directly to your registered email address.',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 11.5,
                      color: AppColors.textSecondary,
                      height: 1.35,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          if (_errorMessage != null) ...[
            _buildErrorAlert(_errorMessage!),
            const SizedBox(height: 16),
          ],

          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: _isSubmitting ? null : _handleSendOtp,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                    )
                  : Text(
                      'Send Verification Code',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLookupTab(int index, String title) {
    final isSelected = _lookupTab == index;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() {
            _lookupTab = index;
            _contactController.clear();
          });
        },
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: isSelected ? AppColors.surface : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            boxShadow: isSelected ? AppShadows.soft : [],
          ),
          alignment: Alignment.center,
          child: Text(
            title,
            style: GoogleFonts.plusJakartaSans(
              fontWeight: FontWeight.w700,
              fontSize: 13,
              color: isSelected ? AppColors.textPrimary : AppColors.textSecondary,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStep2Form() {
    return Form(
      key: _formKeyStep2,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Destination Info Banner
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                const Icon(Icons.mark_email_read_rounded, color: AppColors.primary, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Code sent to registered email:',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      Text(
                        _emailHint?.isNotEmpty == true
                            ? _emailHint!
                            : (_contactController.text.contains('@') ? _contactController.text : 'Registered email'),
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 13,
                          fontWeight: FontWeight.w700,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(
                  onPressed: () => setState(() => _currentStep = 1),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    minimumSize: Size.zero,
                  ),
                  child: Text(
                    'Change',
                    style: GoogleFonts.plusJakartaSans(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),

          // Dev Code Quick Chip
          if (_debugCode != null && _debugCode!.isNotEmpty) ...[
            BouncingCard(
              onTap: () {
                _otpController.text = _debugCode!;
                setState(() {});
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.mintBg,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.mint.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.auto_awesome_rounded, color: AppColors.mint, size: 16),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Demo Code: $_debugCode (Tap to auto-fill)',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: AppColors.mint,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
          ],

          // OTP Input Field
          Text(
            '6-Digit Verification Code',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: _otpController,
            keyboardType: TextInputType.number,
            maxLength: 6,
            textAlign: TextAlign.center,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              letterSpacing: 8,
              color: AppColors.textPrimary,
            ),
            decoration: InputDecoration(
              counterText: '',
              hintText: '••••••',
              hintStyle: GoogleFonts.plusJakartaSans(
                fontSize: 20,
                letterSpacing: 8,
                color: AppColors.textMuted,
              ),
            ),
            validator: (val) {
              if (val == null || val.trim().length != 6) {
                return 'Please enter 6-digit code';
              }
              return null;
            },
          ),
          const SizedBox(height: 18),

          // New Password
          Text(
            'New Password',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: _newPasswordController,
            obscureText: _obscureNewPass,
            style: GoogleFonts.plusJakartaSans(fontSize: 14),
            decoration: InputDecoration(
              hintText: 'Minimum 8 characters',
              prefixIcon: const Icon(Icons.lock_outline_rounded, size: 20, color: AppColors.textMuted),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscureNewPass ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  size: 20,
                  color: AppColors.textMuted,
                ),
                onPressed: () => setState(() => _obscureNewPass = !_obscureNewPass),
              ),
            ),
            validator: (val) {
              if (val == null || val.length < 8) {
                return 'Password must be at least 8 characters';
              }
              return null;
            },
          ),
          const SizedBox(height: 16),

          // Confirm New Password
          Text(
            'Confirm New Password',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: _confirmPasswordController,
            obscureText: _obscureConfirmPass,
            style: GoogleFonts.plusJakartaSans(fontSize: 14),
            decoration: InputDecoration(
              hintText: 'Re-enter your new password',
              prefixIcon: const Icon(Icons.lock_clock_outlined, size: 20, color: AppColors.textMuted),
              suffixIcon: IconButton(
                icon: Icon(
                  _obscureConfirmPass ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                  size: 20,
                  color: AppColors.textMuted,
                ),
                onPressed: () => setState(() => _obscureConfirmPass = !_obscureConfirmPass),
              ),
            ),
            validator: (val) {
              if (val != _newPasswordController.text) {
                return 'Passwords do not match';
              }
              return null;
            },
          ),
          const SizedBox(height: 20),

          // Resend Code timer/button
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Didn\'t receive code?',
                style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textSecondary),
              ),
              _resendSeconds > 0
                  ? Text(
                      'Resend in ${_resendSeconds}s',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textMuted,
                      ),
                    )
                  : TextButton(
                      onPressed: _isSubmitting ? null : _handleSendOtp,
                      style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: Size.zero),
                      child: Text(
                        'Resend Code',
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
            ],
          ),
          const SizedBox(height: 18),

          if (_errorMessage != null) ...[
            _buildErrorAlert(_errorMessage!),
            const SizedBox(height: 16),
          ],

          SizedBox(
            width: double.infinity,
            height: 52,
            child: ElevatedButton(
              onPressed: _isSubmitting ? null : _handleResetPassword,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                    )
                  : Text(
                      'Reset Password',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorAlert(String message) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.dangerBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: GoogleFonts.plusJakartaSans(
                color: AppColors.danger,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
