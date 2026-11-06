import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../shared/providers/auth_provider.dart';
import '../../../shared/widgets/bouncing_card.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  String _selectedTab = 'All';
  bool _isLoading = true;
  String? _error;
  List<Map<String, dynamic>> _notifications = [];
  int _unreadCount = 0;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/notifications');
      final data = response.data;

      if (data != null && data['success'] == true) {
        final list = (data['notifications'] as List? ?? []).map((item) {
          final map = Map<String, dynamic>.from(item as Map);
          return {
            'id': map['id'],
            'title': map['title'] ?? 'Notification',
            'body': map['body'] ?? '',
            'deeplink': map['deeplink'],
            'image_url': map['image_url'],
            'is_read': map['is_read'] == true,
            'time': map['time_ago'] ?? 'Recently',
            'created_at': map['created_at'],
          };
        }).toList();

        setState(() {
          _notifications = list;
          _unreadCount = data['unread_count'] ?? 0;
          _isLoading = false;
        });
      } else {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _error = 'Unable to load notifications. Please pull to refresh.';
      });
    }
  }

  Future<void> _markAllRead() async {
    try {
      final api = ref.read(apiClientProvider);
      await api.post('/notifications/mark-all-read');
      setState(() {
        for (var n in _notifications) {
          n['is_read'] = true;
        }
        _unreadCount = 0;
      });
    } catch (_) {}
  }

  Future<void> _handleNotificationTap(Map<String, dynamic> item) async {
    final id = item['id'];
    final isRead = item['is_read'] as bool;
    final deeplink = item['deeplink']?.toString().trim();

    // Mark as read in backend if currently unread
    if (!isRead) {
      setState(() {
        item['is_read'] = true;
        if (_unreadCount > 0) _unreadCount--;
      });
      try {
        final api = ref.read(apiClientProvider);
        await api.post('/notifications/$id/read');
      } catch (_) {}
    }

    // Trigger deeplink if specified
    if (deeplink != null && deeplink.isNotEmpty && mounted) {
      String targetRoute = deeplink;
      if (targetRoute.startsWith('taskwala://')) {
        targetRoute = targetRoute.replaceFirst('taskwala://', '/');
      }
      if (!targetRoute.startsWith('/')) {
        targetRoute = '/$targetRoute';
      }

      try {
        context.push(targetRoute);
      } catch (_) {}
    }
  }

  Future<void> _toggleReadStatus(Map<String, dynamic> item) async {
    final id = item['id'];
    final isRead = item['is_read'] as bool;
    final newStatus = !isRead;

    setState(() {
      item['is_read'] = newStatus;
      if (newStatus) {
        if (_unreadCount > 0) _unreadCount--;
      } else {
        _unreadCount++;
      }
    });

    try {
      final api = ref.read(apiClientProvider);
      final endpoint = newStatus ? '/notifications/$id/read' : '/notifications/$id/unread';
      await api.post(endpoint);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _notifications.where((n) {
      if (_selectedTab == 'All') return true;
      if (_selectedTab == 'Unread') return n['is_read'] == false;
      if (_selectedTab == 'Read') return n['is_read'] == true;
      return true;
    }).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Row(
          children: [
            Text(
              'Notifications',
              style: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.w800,
                fontSize: 18,
                color: AppColors.textPrimary,
              ),
            ),
            if (_unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '$_unreadCount new',
                  style: GoogleFonts.plusJakartaSans(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 11,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (_notifications.any((n) => n['is_read'] == false))
            TextButton(
              onPressed: _markAllRead,
              child: Text(
                'Mark all read',
                style: GoogleFonts.plusJakartaSans(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 12,
                ),
              ),
            ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          // Filter Tabs
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(bottom: BorderSide(color: AppColors.borderSubtle)),
            ),
            child: Row(
              children: ['All', 'Unread', 'Read'].map((tab) {
                final isSelected = _selectedTab == tab;
                return Padding(
                  padding: const EdgeInsets.only(right: 8.0),
                  child: BouncingCard(
                    onTap: () => setState(() => _selectedTab = tab),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      curve: Curves.easeOutCubic,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
                      decoration: BoxDecoration(
                        color: isSelected ? AppColors.primary : AppColors.surfaceSecondary,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: isSelected ? AppColors.primary : AppColors.border,
                          width: 1,
                        ),
                      ),
                      child: Text(
                        tab == 'Unread' && _unreadCount > 0 ? 'Unread ($_unreadCount)' : tab,
                        style: GoogleFonts.plusJakartaSans(
                          color: isSelected ? Colors.white : AppColors.textSecondary,
                          fontSize: 12,
                          fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
          ),

          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.wifi_off_rounded, size: 48, color: AppColors.textMuted),
                            const SizedBox(height: 12),
                            Text(
                              _error!,
                              style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.textSecondary),
                            ),
                            const SizedBox(height: 16),
                            ElevatedButton(
                              onPressed: _fetchNotifications,
                              child: const Text('Try Again'),
                            ),
                          ],
                        ),
                      )
                    : filtered.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.notifications_none_rounded, size: 54, color: AppColors.textMuted),
                                const SizedBox(height: 12),
                                Text(
                                  _selectedTab == 'Unread' ? 'No unread notifications' : 'No notifications yet',
                                  style: GoogleFonts.plusJakartaSans(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                    color: AppColors.textPrimary,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Special campaign updates and payouts appear here.',
                                  style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textMuted),
                                ),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            onRefresh: _fetchNotifications,
                            child: ListView.builder(
                              physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                              padding: const EdgeInsets.all(16),
                              itemCount: filtered.length,
                              itemBuilder: (context, index) {
                                final item = filtered[index];
                                final isRead = item['is_read'] as bool;
                                final deeplink = item['deeplink']?.toString();

                                return BouncingCard(
                                  onTap: () => _handleNotificationTap(item),
                                  child: Container(
                                    margin: const EdgeInsets.only(bottom: 12),
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: isRead ? AppColors.surface : Colors.white,
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color: isRead
                                            ? AppColors.border
                                            : AppColors.primary.withValues(alpha: 0.35),
                                        width: isRead ? 1 : 1.5,
                                      ),
                                      boxShadow: isRead ? null : AppShadows.soft,
                                    ),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        // Notification Icon Badge
                                        Container(
                                          width: 44,
                                          height: 44,
                                          decoration: BoxDecoration(
                                            color: isRead ? AppColors.surfaceSecondary : AppColors.primaryLight,
                                            borderRadius: BorderRadius.circular(14),
                                          ),
                                          child: Icon(
                                            Icons.notifications_active_rounded,
                                            color: isRead ? AppColors.textMuted : AppColors.primary,
                                            size: 22,
                                          ),
                                        ),
                                        const SizedBox(width: 14),

                                        // Content
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Expanded(
                                                    child: Text(
                                                      item['title'] as String,
                                                      style: GoogleFonts.plusJakartaSans(
                                                        fontWeight: isRead ? FontWeight.w600 : FontWeight.w800,
                                                        fontSize: 14,
                                                        color: AppColors.textPrimary,
                                                      ),
                                                    ),
                                                  ),
                                                  if (!isRead)
                                                    Container(
                                                      margin: const EdgeInsets.only(left: 6, top: 4),
                                                      width: 8,
                                                      height: 8,
                                                      decoration: const BoxDecoration(
                                                        color: AppColors.primary,
                                                        shape: BoxShape.circle,
                                                      ),
                                                    ),
                                                ],
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                item['body'] as String,
                                                style: GoogleFonts.plusJakartaSans(
                                                  fontSize: 12.5,
                                                  color: AppColors.textSecondary,
                                                  height: 1.35,
                                                ),
                                              ),
                                              const SizedBox(height: 8),

                                              Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Text(
                                                    item['time'] as String,
                                                    style: GoogleFonts.plusJakartaSans(
                                                      fontSize: 11,
                                                      fontWeight: FontWeight.w500,
                                                      color: AppColors.textMuted,
                                                    ),
                                                  ),
                                                  Row(
                                                    children: [
                                                      if (deeplink != null && deeplink.isNotEmpty)
                                                        Container(
                                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                                          decoration: BoxDecoration(
                                                            color: AppColors.primaryLight,
                                                            borderRadius: BorderRadius.circular(10),
                                                          ),
                                                          child: Row(
                                                            children: [
                                                              Text(
                                                                'Open',
                                                                style: GoogleFonts.plusJakartaSans(
                                                                  color: AppColors.primary,
                                                                  fontWeight: FontWeight.w700,
                                                                  fontSize: 10.5,
                                                                ),
                                                             ),
                                                             const SizedBox(width: 3),
                                                             const Icon(Icons.arrow_forward_ios_rounded, size: 9, color: AppColors.primary),
                                                           ],
                                                         ),
                                                       ),
                                                      const SizedBox(width: 8),
                                                      IconButton(
                                                        icon: Icon(
                                                          isRead ? Icons.mark_email_unread_outlined : Icons.mark_email_read_outlined,
                                                          size: 18,
                                                          color: AppColors.textMuted,
                                                        ),
                                                        tooltip: isRead ? 'Mark as Unread' : 'Mark as Read',
                                                        padding: EdgeInsets.zero,
                                                        constraints: const BoxConstraints(),
                                                        onPressed: () => _toggleReadStatus(item),
                                                      ),
                                                    ],
                                                  ),
                                                ],
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ).animate().fadeIn(delay: (index * 30).ms, duration: 250.ms).slideY(begin: 0.04, end: 0);
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}
