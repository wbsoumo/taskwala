import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../shared/providers/auth_provider.dart';
import '../../shared/models/models.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../features/dashboard/presentation/main_shell_screen.dart';
import '../../features/dashboard/presentation/dashboard_screen.dart';
import '../../features/campaigns/presentation/campaigns_screen.dart';
import '../../features/links/presentation/link_generator_screen.dart';
import '../../features/links/presentation/my_links_screen.dart';
import '../../features/reports/presentation/reports_screen.dart';
import '../../features/wallet/presentation/wallet_screen.dart';
import '../../features/wallet/presentation/payout_screen.dart';
import '../../features/referrals/presentation/referral_dashboard_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../../features/support/presentation/support_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final isLoggingIn = state.matchedLocation == '/login' || state.matchedLocation == '/register';

      if (authState.isLoading) return null;

      if (!authState.isAuthenticated && !isLoggingIn) {
        return '/login';
      }

      if (authState.isAuthenticated && isLoggingIn) {
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
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/campaigns',
            builder: (context, state) => const CampaignsScreen(),
          ),
          GoRoute(
            path: '/links',
            builder: (context, state) => const MyLinksScreen(),
          ),
          GoRoute(
            path: '/reports',
            builder: (context, state) => const ReportsScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/link-generator',
        builder: (context, state) {
          final campaign = state.extra as Campaign?;
          if (campaign == null) {
            return const CampaignsScreen();
          }
          return LinkGeneratorScreen(campaign: campaign);
        },
      ),
      GoRoute(
        path: '/wallet',
        builder: (context, state) => const WalletScreen(),
      ),
      GoRoute(
        path: '/payout',
        builder: (context, state) => const PayoutScreen(),
      ),
      GoRoute(
        path: '/referral',
        builder: (context, state) => const ReferralDashboardScreen(),
      ),
      GoRoute(
        path: '/notifications',
        builder: (context, state) => const NotificationsScreen(),
      ),
      GoRoute(
        path: '/support',
        builder: (context, state) => const SupportScreen(),
      ),
    ],
  );
});
