import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../shared/providers/auth_provider.dart';
import '../../shared/models/models.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../features/auth/presentation/forgot_password_screen.dart';
import '../../features/auth/presentation/email_verification_screen.dart';
import '../../features/dashboard/presentation/main_shell_screen.dart';
import '../../features/dashboard/presentation/dashboard_screen.dart';
import '../../features/campaigns/presentation/campaigns_screen.dart';
import '../../features/links/presentation/link_generator_screen.dart';
import '../../features/links/presentation/my_links_screen.dart';
import '../../features/reports/presentation/reports_screen.dart';
import '../../features/wallet/presentation/wallet_screen.dart';
import '../../features/wallet/presentation/payout_screen.dart';
import '../../features/wallet/presentation/withdrawal_status_screen.dart';
import '../../features/referrals/presentation/referral_dashboard_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../../features/support/presentation/support_screen.dart';
import '../../features/reports/presentation/missing_ticket_screen.dart';
import '../../features/deals/presentation/deals_screen.dart';

/// Helper for silky smooth slide & fade page transitions
CustomTransitionPage<T> _buildSmoothPage<T>({
  required BuildContext context,
  required GoRouterState state,
  required Widget child,
}) {
  return CustomTransitionPage<T>(
    key: state.pageKey,
    child: child,
    transitionDuration: const Duration(milliseconds: 320),
    reverseTransitionDuration: const Duration(milliseconds: 260),
    transitionsBuilder: (context, animation, secondaryAnimation, child) {
      final curvedAnimation = CurvedAnimation(
        parent: animation,
        curve: Curves.easeOutCubic,
        reverseCurve: Curves.easeInCubic,
      );

      return FadeTransition(
        opacity: Tween<double>(begin: 0.0, end: 1.0).animate(curvedAnimation),
        child: SlideTransition(
          position: Tween<Offset>(
            begin: const Offset(0, 0.04),
            end: Offset.zero,
          ).animate(curvedAnimation),
          child: child,
        ),
      );
    },
  );
}

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      final isAuthRoute = state.matchedLocation == '/login' ||
          state.matchedLocation == '/register' ||
          state.matchedLocation == '/forgot-password' ||
          state.matchedLocation == '/verify-email';

      final currentAuth = ref.read(authProvider);

      if (!currentAuth.isAuthenticated && !isAuthRoute) {
        return '/login';
      }

      if (currentAuth.isAuthenticated && isAuthRoute) {
        return '/';
      }

      return null;
    },
    routes: [
      ShellRoute(
        builder: (context, state, child) {
          return MainShellScreen(child: child);
        },
        routes: [
          GoRoute(
            path: '/',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const DashboardScreen(),
            ),
          ),
          GoRoute(
            path: '/campaigns',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const CampaignsScreen(),
            ),
          ),
          GoRoute(
            path: '/links',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const MyLinksScreen(),
            ),
          ),
          GoRoute(
            path: '/deals',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const DealsScreen(),
            ),
          ),
          GoRoute(
            path: '/reports',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const ReportsScreen(),
            ),
          ),
          GoRoute(
            path: '/profile',
            pageBuilder: (context, state) => _buildSmoothPage(
              context: context,
              state: state,
              child: const ProfileScreen(),
            ),
          ),
        ],
      ),
      GoRoute(
        path: '/login',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const LoginScreen(),
        ),
      ),
      GoRoute(
        path: '/register',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const RegisterScreen(),
        ),
      ),
      GoRoute(
        path: '/forgot-password',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const ForgotPasswordScreen(),
        ),
      ),
      GoRoute(
        path: '/verify-email',
        pageBuilder: (context, state) {
          final extra = state.extra as Map<String, dynamic>? ?? {};
          final email = extra['email']?.toString() ?? '';
          final debugCode = extra['debug_code']?.toString();
          return _buildSmoothPage(
            context: context,
            state: state,
            child: EmailVerificationScreen(
              email: email,
              debugCode: debugCode,
            ),
          );
        },
      ),
      GoRoute(
        path: '/link-generator',
        pageBuilder: (context, state) {
          final campaign = state.extra as Campaign?;
          if (campaign == null) {
            return _buildSmoothPage(
              context: context,
              state: state,
              child: const CampaignsScreen(),
            );
          }
          return _buildSmoothPage(
            context: context,
            state: state,
            child: LinkGeneratorScreen(campaign: campaign),
          );
        },
      ),
      GoRoute(
        path: '/wallet',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const WalletScreen(),
        ),
      ),
      GoRoute(
        path: '/payout',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const PayoutScreen(),
        ),
      ),
      GoRoute(
        path: '/withdraw',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const PayoutScreen(),
        ),
      ),
      GoRoute(
        path: '/withdraw/status',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const WithdrawalStatusScreen(),
        ),
      ),
      GoRoute(
        path: '/withdrawals',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const WithdrawalStatusScreen(),
        ),
      ),
      GoRoute(
        path: '/referral',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const ReferralDashboardScreen(),
        ),
      ),
      GoRoute(
        path: '/notifications',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const NotificationsScreen(),
        ),
      ),
      GoRoute(
        path: '/support',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const SupportScreen(),
        ),
      ),
      GoRoute(
        path: '/tickets',
        pageBuilder: (context, state) {
          final campaignIdStr = state.uri.queryParameters['campaign_id'];
          final initialCampaignId = campaignIdStr != null ? int.tryParse(campaignIdStr) : null;
          return _buildSmoothPage(
            context: context,
            state: state,
            child: MissingTicketScreen(initialCampaignId: initialCampaignId),
          );
        },
      ),
      GoRoute(
        path: '/missing-tickets',
        pageBuilder: (context, state) => _buildSmoothPage(
          context: context,
          state: state,
          child: const MissingTicketScreen(),
        ),
      ),
    ],
  );
});

class _AuthListenable extends ChangeNotifier {
  _AuthListenable(Ref ref) {
    ref.listen<AuthState>(authProvider, (_, __) {
      notifyListeners();
    });
  }
}
