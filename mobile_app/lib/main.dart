import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/theme/app_theme.dart';
import 'core/routing/app_router.dart';
import 'core/notifications/notification_service.dart';
import 'shared/providers/auth_provider.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    const ProviderScope(
      child: TaskwalaApp(),
    ),
  );
}

class TaskwalaApp extends ConsumerStatefulWidget {
  const TaskwalaApp({super.key});

  @override
  ConsumerState<TaskwalaApp> createState() => _TaskwalaAppState();
}

class _TaskwalaAppState extends ConsumerState<TaskwalaApp> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final apiClient = ref.read(apiClientProvider);
      final router = ref.read(routerProvider);
      NotificationService().initialize(apiClient: apiClient, router: router);
    });
  }

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'Taskwala Partner',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      routerConfig: router,
    );
  }
}
