import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/widgets/bouncing_card.dart';

class EmailVerificationScreen extends ConsumerStatefulWidget {
  final String email;
  final String? debugCode;

  const EmailVerificationScreen({
    super.key,
    required this.email,
    this.debugCode,
  });

  @override
  ConsumerState<EmailVerificationScreen> createState() => _EmailVerificationScreenState();
}

class _EmailVerificationScreenState extends ConsumerState<EmailVerificationScreen> {
  final List<TextEditingController> _controllers = List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _focusNodes = List.generate(6, (_) => FocusNode());

  bool _isSubmitting = false;
  bool _isResending = false;
  String? _errorMessage;

  // Resend Countdown
  Timer? _resendTimer;
  int _resendSeconds = 60;

  @override
  void initState() {
    super.initState();
    _startResendTimer();
  }

  @override
  void dispose() {
    _resendTimer?.cancel();
    for (final c in _controllers) {
      c.dispose();
    }
    for (final fn in _focusNodes) {
      fn.dispose();
    }
    super.dispose();
  }

  void _startResendTimer() {
    _resendTimer?.cancel();
    setState(() => _resendSeconds = 60);
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      if (_resendSeconds <= 1) {
        timer.cancel();
        setState(() => _resendSeconds = 0);
      } else {
        setState(() => _resendSeconds--);
      }
    });
  }


  String _getOtp() {
    return _controllers.map((c) => c.text.trim()).join();
  }

  Future<void> _handleVerify() async {
    final otp = _getOtp();
    if (otp.length < 6) {
      setState(() => _errorMessage = 'Please enter the complete 6-digit verification code.');
      HapticFeedback.mediumImpact();
      return;
    }

    FocusScope.of(context).unfocus();
    setState(() {
      _isSubmitting = true;
      _errorMessage = null;
    });

    HapticFeedback.lightImpact();

    final result = await ref.read(authProvider.notifier).verifyEmail(
          email: widget.email,
          otp: otp,
        );

    if (!mounted) return;

    if (result.success) {
      setState(() {
        _isSubmitting = false;
      });
      HapticFeedback.heavyImpact();

      // Show success bottom notification and navigate to home
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.mint,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          content: Row(
            children: [
              const Icon(Icons.check_circle_rounded, color: Colors.white, size: 22),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Account activated! Welcome to Taskwala.',
                  style: GoogleFonts.plusJakartaSans(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
        ),
      );

      // Navigate to main shell
      context.go('/');
    } else {
      setState(() {
        _isSubmitting = false;
        _errorMessage = result.message ?? 'Invalid verification code. Please try again.';
      });
      HapticFeedback.mediumImpact();
    }
  }

  Future<void> _handleResend() async {
    if (_resendSeconds > 0 || _isResending) return;

    setState(() {
      _isResending = true;
      _errorMessage = null;
    });

    HapticFeedback.lightImpact();

    final res = await ref.read(authProvider.notifier).resendVerificationEmail(widget.email);

    if (!mounted) return;

    setState(() {
      _isResending = false;
    });

    if (res['success'] == true) {
      _startResendTimer();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.surface,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: const BorderSide(color: AppColors.border),
          ),
          content: Text(
            res['message']?.toString() ?? 'A new verification code has been sent to your email.',
            style: GoogleFonts.plusJakartaSans(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w600,
              fontSize: 13,
            ),
          ),
        ),
      );
    } else {
      setState(() {
        _errorMessage = res['message']?.toString() ?? 'Failed to resend code. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final timerText = '00:${_resendSeconds.toString().padLeft(2, '0')}';

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
          onPressed: () => context.pop(),
        ),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 12.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Top Icon Badge with Pulse / Glow
                Center(
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [AppColors.primaryGradientStart, AppColors.primaryGradientEnd],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(26),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.primary.withValues(alpha: 0.35),
                              blurRadius: 24,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.mark_email_unread_rounded,
                          size: 42,
                          color: Colors.white,
                        ),
                      ),
                      Positioned(
                        right: 0,
                        bottom: 0,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: const BoxDecoration(
                            color: AppColors.mint,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.check_rounded,
                            size: 14,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ).animate().scale(duration: 450.ms, curve: Curves.easeOutBack),

                const SizedBox(height: 22),

                // Headline
                Text(
                  'Verify Your Email',
                  textAlign: TextAlign.center,
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                    letterSpacing: -0.5,
                  ),
                ).animate().fadeIn(delay: 100.ms),

                const SizedBox(height: 8),

                // Description with Email highlight
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16.0),
                  child: RichText(
                    textAlign: TextAlign.center,
                    text: TextSpan(
                      text: 'We have sent a 6-digit verification code to\n',
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 13,
                        color: AppColors.textSecondary,
                        height: 1.45,
                      ),
                      children: [
                        TextSpan(
                          text: widget.email,
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ).animate().fadeIn(delay: 150.ms),

                const SizedBox(height: 16),



                const SizedBox(height: 24),

                // Main Form Card
                Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: AppColors.border),
                    boxShadow: AppShadows.card,
                  ),
                  child: Column(
                    children: [
                      // Error / Success banners
                      if (_errorMessage != null) ...[
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: AppColors.danger.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.error_outline_rounded, color: AppColors.danger, size: 18),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  _errorMessage!,
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.danger,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                      ],

                      // 6-digit OTP Input Fields
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: List.generate(6, (index) => _buildOtpBox(index)),
                      ),

                      const SizedBox(height: 24),

                      // Verify & Continue Button
                      BouncingCard(
                        onTap: _isSubmitting ? null : _handleVerify,
                        child: Container(
                          width: double.infinity,
                          height: 52,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [AppColors.primaryGradientStart, AppColors.primaryGradientEnd],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withValues(alpha: 0.35),
                                blurRadius: 16,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          alignment: Alignment.center,
                          child: _isSubmitting
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.5,
                                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                  ),
                                )
                              : Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      'Verify & Continue',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 15,
                                        fontWeight: FontWeight.w700,
                                        color: Colors.white,
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 18),
                                  ],
                                ),
                        ),
                      ),

                      const SizedBox(height: 18),

                      // Resend Code Row
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            "Didn't receive the email? ",
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 12,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          if (_resendSeconds > 0)
                            Text(
                              'Resend in $timerText',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: AppColors.primary,
                              ),
                            )
                          else
                            GestureDetector(
                              onTap: _isResending ? null : _handleResend,
                              child: _isResending
                                  ? const SizedBox(
                                      width: 14,
                                      height: 14,
                                      child: CircularProgressIndicator(strokeWidth: 2),
                                    )
                                  : Text(
                                      'Resend Code',
                                      style: GoogleFonts.plusJakartaSans(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.primary,
                                        decoration: TextDecoration.underline,
                                      ),
                                    ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ).animate().fadeIn(delay: 250.ms),

                const SizedBox(height: 24),

                // Back to login or change account
                Center(
                  child: TextButton(
                    onPressed: () => context.go('/login'),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.arrow_back_rounded, size: 16, color: AppColors.textSecondary),
                        const SizedBox(width: 6),
                        Text(
                          'Back to Login',
                          style: GoogleFonts.plusJakartaSans(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textSecondary,
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
      ),
    );
  }

  Widget _buildOtpBox(int index) {
    return SizedBox(
      width: 44,
      height: 52,
      child: KeyboardListener(
        focusNode: FocusNode(),
        onKeyEvent: (event) {
          if (event is KeyDownEvent &&
              event.logicalKey == LogicalKeyboardKey.backspace &&
              _controllers[index].text.isEmpty &&
              index > 0) {
            _focusNodes[index - 1].requestFocus();
          }
        },
        child: TextFormField(
          controller: _controllers[index],
          focusNode: _focusNodes[index],
          keyboardType: TextInputType.number,
          textAlign: TextAlign.center,
          maxLength: 1,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            color: AppColors.textPrimary,
          ),
          decoration: InputDecoration(
            counterText: '',
            contentPadding: EdgeInsets.zero,
            filled: true,
            fillColor: _controllers[index].text.isNotEmpty ? AppColors.primaryLight : AppColors.surfaceSecondary,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.border),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: _controllers[index].text.isNotEmpty ? AppColors.primary : AppColors.border,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.primary, width: 2),
            ),
          ),
          inputFormatters: [
            FilteringTextInputFormatter.digitsOnly,
          ],
          onChanged: (val) {
            setState(() {});
            if (val.isNotEmpty) {
              if (index < 5) {
                _focusNodes[index + 1].requestFocus();
              } else {
                _focusNodes[index].unfocus();
                _handleVerify();
              }
            } else if (val.isEmpty && index > 0) {
              _focusNodes[index - 1].requestFocus();
            }
          },
        ),
      ),
    );
  }
}
