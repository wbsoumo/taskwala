import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/providers/auth_provider.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _startSessionCheck();
  }

  Future<void> _startSessionCheck() async {
    // 1. Minimum display duration so the logo reveal GIF animation plays smoothly
    final minDelayFuture = Future.delayed(const Duration(milliseconds: 2600));

    // 2. Perform background session/auth check from local storage & API (with safety timeout)
    final authCheckFuture = ref
        .read(authProvider.notifier)
        .checkAuthStatus()
        .timeout(const Duration(seconds: 4), onTimeout: () {});

    // Wait for both animation presentation and auth check to complete
    await Future.wait([minDelayFuture, authCheckFuture]);

    if (!mounted) return;

    final isAuthenticated = ref.read(authProvider).isAuthenticated;

    if (isAuthenticated) {
      context.go('/');
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.dark,
        statusBarBrightness: Brightness.light,
        systemNavigationBarColor: Colors.white,
        systemNavigationBarIconBrightness: Brightness.dark,
      ),
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: ConstrainedBox(
                constraints: BoxConstraints(
                  maxWidth: size.width * 0.85,
                  maxHeight: size.height * 0.60,
                ),
                child: Image.asset(
                  'assets/images/splash_animation.gif',
                  fit: BoxFit.contain,
                  filterQuality: FilterQuality.high,
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
