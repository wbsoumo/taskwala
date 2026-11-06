import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shimmer/shimmer.dart';
import '../../core/theme/app_theme.dart';

class CampaignLogo extends StatelessWidget {
  final String? logoUrl;
  final String name;
  final String? category;
  final double size;
  final double borderRadius;
  final Color? backgroundColor;
  final BoxBorder? border;
  final BoxFit fit;

  const CampaignLogo({
    super.key,
    this.logoUrl,
    required this.name,
    this.category,
    this.size = 46.0,
    this.borderRadius = 14.0,
    this.backgroundColor,
    this.border,
    this.fit = BoxFit.contain,
  });

  bool get _hasValidUrl {
    if (logoUrl == null) return false;
    final trimmed = logoUrl!.trim();
    return trimmed.isNotEmpty &&
        (trimmed.startsWith('http://') || trimmed.startsWith('https://'));
  }

  @override
  Widget build(BuildContext context) {
    final catColors = AppColors.getCategoryColors(category ?? name);
    final effectiveBorder = border ??
        Border.all(
          color: catColors.fg.withValues(alpha: 0.18),
          width: 1.2,
        );

    final fallbackBadge = Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: backgroundColor ?? catColors.bg,
        borderRadius: BorderRadius.circular(borderRadius),
        border: effectiveBorder,
      ),
      alignment: Alignment.center,
      child: Text(
        name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'T',
        style: GoogleFonts.plusJakartaSans(
          fontSize: size * 0.42,
          fontWeight: FontWeight.w800,
          color: catColors.fg,
        ),
      ),
    );

    if (!_hasValidUrl) {
      return fallbackBadge;
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: backgroundColor ?? Colors.white,
        borderRadius: BorderRadius.circular(borderRadius),
        border: effectiveBorder,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Image.network(
        logoUrl!.trim(),
        width: size,
        height: size,
        fit: fit,
        loadingBuilder: (context, child, loadingProgress) {
          if (loadingProgress == null) return child;
          return Shimmer.fromColors(
            baseColor: Colors.grey.shade200,
            highlightColor: Colors.white,
            child: Container(
              width: size,
              height: size,
              color: Colors.grey.shade200,
            ),
          );
        },
        errorBuilder: (context, error, stackTrace) {
          return fallbackBadge;
        },
      ),
    );
  }
}
