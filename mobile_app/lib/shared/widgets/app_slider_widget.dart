import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';

import '../../core/theme/app_theme.dart';
import '../models/models.dart';
import '../providers/app_providers.dart';

class AppSliderWidget extends ConsumerStatefulWidget {
  final List<AppSliderItem>? sliders;
  final double height;

  const AppSliderWidget({
    super.key,
    this.sliders,
    this.height = 168.0,
  });

  @override
  ConsumerState<AppSliderWidget> createState() => _AppSliderWidgetState();
}

class _AppSliderWidgetState extends ConsumerState<AppSliderWidget> {
  late final PageController _pageController;
  int _currentPage = 0;
  Timer? _autoScrollTimer;
  bool _isUserInteracting = false;

  @override
  void initState() {
    super.initState();
    _pageController = PageController(viewportFraction: 1.0);
    _startAutoScroll();
  }

  @override
  void dispose() {
    _autoScrollTimer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  void _startAutoScroll() {
    _autoScrollTimer?.cancel();
    _autoScrollTimer = Timer.periodic(const Duration(seconds: 6), (timer) {
      if (!mounted || _isUserInteracting) return;
      final items = widget.sliders ?? ref.read(appSlidersProvider).asData?.value ?? [];
      if (items.length <= 1) return;

      final nextPage = (_currentPage + 1) % items.length;
      _pageController.animateToPage(
        nextPage,
        duration: const Duration(milliseconds: 550),
        curve: Curves.easeInOutCubic,
      );
    });
  }

  void _handleTap(AppSliderItem item) async {
    if (!item.hasAction) return;

    final actionType = item.actionType.toLowerCase();
    final actionValue = item.actionValue?.trim() ?? '';
    if (actionValue.isEmpty) return;

    try {
      if (actionType == 'campaign') {
        // Try to match campaign in loaded campaigns
        final campaigns = ref.read(campaignsProvider).asData?.value ?? [];
        Campaign? matched;
        for (final c in campaigns) {
          if (c.id.toString() == actionValue || c.name.toLowerCase() == actionValue.toLowerCase()) {
            matched = c;
            break;
          }
        }
        if (matched != null) {
          context.push('/link-generator', extra: matched);
        } else {
          context.push('/campaigns');
        }
      } else if (actionType == 'deal') {
        context.push('/deals');
      } else if (actionType == 'app_route') {
        final route = actionValue.startsWith('/') ? actionValue : '/$actionValue';
        context.push(route);
      } else if (actionType == 'external_url') {
        final uri = Uri.tryParse(actionValue);
        if (uri != null) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      }
    } catch (e) {
      debugPrint('Error navigating slider action ($actionType -> $actionValue): $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final items = widget.sliders ?? ref.watch(appSlidersProvider).asData?.value ?? [];

    if (items.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          height: widget.height,
          child: NotificationListener<ScrollNotification>(
            onNotification: (notification) {
              if (notification is ScrollStartNotification) {
                _isUserInteracting = true;
              } else if (notification is ScrollEndNotification) {
                _isUserInteracting = false;
              }
              return false;
            },
            child: PageView.builder(
              controller: _pageController,
              itemCount: items.length,
              onPageChanged: (index) {
                setState(() => _currentPage = index);
              },
              itemBuilder: (context, index) {
                final item = items[index];
                final isCurrent = index == _currentPage;

                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 4.0),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: () => _handleTap(item),
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.08),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        clipBehavior: Clip.antiAlias,
                        child: Stack(
                          fit: StackFit.expand,
                          children: [
                            // MEDIA CONTENT (Video or Image)
                            if (item.isVideo)
                              _VideoSliderItem(
                                item: item,
                                isActive: isCurrent,
                              )
                            else
                              _ImageSliderItem(item: item),

                            // GRADIENT OVERLAY FOR TEXT READABILITY
                            Positioned.fill(
                              child: DecoratedBox(
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                    colors: [
                                      Colors.transparent,
                                      Colors.black.withValues(alpha: 0.25),
                                      Colors.black.withValues(alpha: 0.78),
                                    ],
                                    stops: const [0.4, 0.7, 1.0],
                                  ),
                                ),
                              ),
                            ),

                            // BADGE (TOP LEFT)
                            if (item.badgeText != null && item.badgeText!.isNotEmpty)
                              Positioned(
                                top: 10,
                                left: 12,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    gradient: const LinearGradient(
                                      colors: [Color(0xFFFF416C), Color(0xFFFF4B2B)],
                                    ),
                                    borderRadius: BorderRadius.circular(8),
                                    boxShadow: [
                                      BoxShadow(
                                        color: const Color(0xFFFF4B2B).withValues(alpha: 0.35),
                                        blurRadius: 6,
                                        offset: const Offset(0, 2),
                                      ),
                                    ],
                                  ),
                                  child: Text(
                                    item.badgeText!.toUpperCase(),
                                    style: GoogleFonts.plusJakartaSans(
                                      color: Colors.white,
                                      fontSize: 10,
                                      fontWeight: FontWeight.w800,
                                      letterSpacing: 0.5,
                                    ),
                                  ),
                                ),
                              ),

                            // MEDIA TYPE BADGE (TOP RIGHT for video)
                            if (item.isVideo)
                              Positioned(
                                top: 10,
                                right: 12,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: Colors.black.withValues(alpha: 0.65),
                                    borderRadius: BorderRadius.circular(6),
                                    border: Border.all(
                                      color: Colors.white.withValues(alpha: 0.25),
                                      width: 0.8,
                                    ),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(Icons.videocam_rounded, color: Colors.white, size: 12),
                                      const SizedBox(width: 4),
                                      Text(
                                        'VIDEO',
                                        style: GoogleFonts.plusJakartaSans(
                                          color: Colors.white,
                                          fontSize: 9,
                                          fontWeight: FontWeight.w700,
                                          letterSpacing: 0.4,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),

                            // TEXT CONTENT (BOTTOM)
                            Positioned(
                              left: 14,
                              right: 14,
                              bottom: 12,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  if (item.title != null && item.title!.isNotEmpty)
                                    Text(
                                      item.title!,
                                      style: GoogleFonts.plusJakartaSans(
                                        color: Colors.white,
                                        fontSize: 15,
                                        fontWeight: FontWeight.w800,
                                        height: 1.2,
                                        shadows: const [
                                          Shadow(color: Colors.black54, blurRadius: 4, offset: Offset(0, 1)),
                                        ],
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  if (item.subtitle != null && item.subtitle!.isNotEmpty) ...[
                                    const SizedBox(height: 3),
                                    Text(
                                      item.subtitle!,
                                      style: GoogleFonts.plusJakartaSans(
                                        color: Colors.white.withValues(alpha: 0.9),
                                        fontSize: 11,
                                        fontWeight: FontWeight.w500,
                                        height: 1.25,
                                      ),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ],
                              ),
                            ),

                            // ACTION ARROW (BOTTOM RIGHT)
                            if (item.hasAction)
                              Positioned(
                                right: 12,
                                bottom: 12,
                                child: Container(
                                  padding: const EdgeInsets.all(5),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.22),
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: Colors.white.withValues(alpha: 0.35),
                                      width: 0.8,
                                    ),
                                  ),
                                  child: const Icon(
                                    Icons.arrow_forward_rounded,
                                    color: Colors.white,
                                    size: 14,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ),

        // DOT INDICATORS (IF MULTIPLE ITEMS)
        if (items.length > 1) ...[
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(items.length, (index) {
              final isSelected = index == _currentPage;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 250),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                height: 5,
                width: isSelected ? 18 : 6,
                decoration: BoxDecoration(
                  color: isSelected ? AppColors.primary : Colors.grey.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(3),
                ),
              );
            }),
          ),
        ],
      ],
    );
  }
}

class _ImageSliderItem extends StatelessWidget {
  final AppSliderItem item;

  const _ImageSliderItem({required this.item});

  @override
  Widget build(BuildContext context) {
    if (item.mediaUrl.isEmpty) {
      return Container(
        color: AppColors.primary.withValues(alpha: 0.1),
        child: const Center(
          child: Icon(Icons.image, color: Colors.grey, size: 36),
        ),
      );
    }

    return Image.network(
      item.mediaUrl,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) {
        return Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [
                AppColors.primary.withValues(alpha: 0.8),
                AppColors.secondary.withValues(alpha: 0.8),
              ],
            ),
          ),
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.campaign_rounded, color: Colors.white, size: 32),
                if (item.title != null) ...[
                  const SizedBox(height: 4),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      item.title!,
                      textAlign: TextAlign.center,
                      style: GoogleFonts.plusJakartaSans(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        );
      },
      loadingBuilder: (context, child, loadingProgress) {
        if (loadingProgress == null) return child;
        return Container(
          color: Colors.grey.withValues(alpha: 0.1),
          child: const Center(
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation<Color>(AppColors.primary),
            ),
          ),
        );
      },
    );
  }
}

class _VideoSliderItem extends StatefulWidget {
  final AppSliderItem item;
  final bool isActive;

  const _VideoSliderItem({
    required this.item,
    required this.isActive,
  });

  @override
  State<_VideoSliderItem> createState() => _VideoSliderItemState();
}

class _VideoSliderItemState extends State<_VideoSliderItem> {
  VideoPlayerController? _controller;
  bool _isInitialized = false;
  bool _isMuted = true;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _initVideo();
  }

  void _initVideo() async {
    final url = widget.item.mediaUrl.trim();
    if (url.isEmpty) {
      setState(() => _hasError = true);
      return;
    }

    try {
      final uri = Uri.parse(url);
      _controller = VideoPlayerController.networkUrl(uri);
      await _controller!.initialize();

      if (!mounted) {
        _controller?.dispose();
        return;
      }

      await _controller!.setVolume(0.0); // Start muted for smooth UX
      if (widget.item.loopVideo) {
        await _controller!.setLooping(true);
      }

      setState(() {
        _isInitialized = true;
      });

      if (widget.isActive && widget.item.autoPlayVideo) {
        _controller!.play();
      }
    } catch (e) {
      debugPrint('Error initializing slider video ($url): $e');
      if (mounted) {
        setState(() => _hasError = true);
      }
    }
  }

  @override
  void didUpdateWidget(covariant _VideoSliderItem oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_controller != null && _isInitialized) {
      if (widget.isActive && !oldWidget.isActive) {
        if (widget.item.autoPlayVideo) {
          _controller!.play();
        }
      } else if (!widget.isActive && oldWidget.isActive) {
        _controller!.pause();
      }
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  void _toggleSound() async {
    if (_controller == null || !_isInitialized) return;
    final newMuted = !_isMuted;
    await _controller!.setVolume(newMuted ? 0.0 : 1.0);
    setState(() => _isMuted = newMuted);
  }

  @override
  Widget build(BuildContext context) {
    if (_hasError) {
      // Fallback to thumbnail or placeholder
      if (widget.item.thumbnailUrl != null && widget.item.thumbnailUrl!.isNotEmpty) {
        return Image.network(
          widget.item.thumbnailUrl!,
          fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => _errorPlaceholder(),
        );
      }
      return _errorPlaceholder();
    }

    if (!_isInitialized || _controller == null) {
      return Stack(
        fit: StackFit.expand,
        children: [
          if (widget.item.thumbnailUrl != null && widget.item.thumbnailUrl!.isNotEmpty)
            Image.network(
              widget.item.thumbnailUrl!,
              fit: BoxFit.cover,
            )
          else
            Container(color: Colors.black87),
          const Center(
            child: CircularProgressIndicator(
              strokeWidth: 2,
              valueColor: AlwaysStoppedAnimation<Color>(AppColors.primary),
            ),
          ),
        ],
      );
    }

    return Stack(
      fit: StackFit.expand,
      children: [
        FittedBox(
          fit: BoxFit.cover,
          child: SizedBox(
            width: _controller!.value.size.width,
            height: _controller!.value.size.height,
            child: VideoPlayer(_controller!),
          ),
        ),

        // MUTE / UNMUTE BUTTON (TOP RIGHT UNDER BADGE)
        Positioned(
          top: 36,
          right: 12,
          child: GestureDetector(
            onTap: _toggleSound,
            child: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.6),
                shape: BoxShape.circle,
                border: Border.all(
                  color: Colors.white.withValues(alpha: 0.25),
                  width: 0.8,
                ),
              ),
              child: Icon(
                _isMuted ? Icons.volume_off_rounded : Icons.volume_up_rounded,
                color: Colors.white,
                size: 14,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _errorPlaceholder() {
    return Container(
      color: Colors.black87,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.play_circle_outline, color: Colors.white60, size: 36),
            const SizedBox(height: 4),
            Text(
              'Video Unavailable',
              style: GoogleFonts.plusJakartaSans(color: Colors.white60, fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }
}
