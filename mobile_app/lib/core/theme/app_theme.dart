import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  // Primary Vibrant Palette (FlutterLibrary / The Flutter Way style)
  static const Color primary = Color(0xFF5E50EE); // Modern Vibrant Indigo Violet
  static const Color primaryDark = Color(0xFF4536DF);
  static const Color primaryLight = Color(0xFFEEECFF);
  static const Color primaryGradientStart = Color(0xFF6C5CE7);
  static const Color primaryGradientEnd = Color(0xFF4834D4);

  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primaryGradientStart, primaryGradientEnd],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient heroGradient = LinearGradient(
    colors: [Color(0xFF5E50EE), Color(0xFF4536DF), Color(0xFF3829C4)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const Color secondary = Color(0xFF8C7CFF);
  static const Color accent = Color(0xFF00D2D3);
  static const Color accentLight = Color(0xFFE0F7F6);

  // Friendly Colorful Accents & Category Tints
  static const Color mint = Color(0xFF10B981);
  static const Color mintBg = Color(0xFFECFDF5);

  static const Color coral = Color(0xFFFF5E7E);
  static const Color coralBg = Color(0xFFFFF1F4);

  static const Color sky = Color(0xFF0EA5E9);
  static const Color skyBg = Color(0xFFF0F9FF);

  static const Color amber = Color(0xFFF59E0B);
  static const Color amberBg = Color(0xFFFFFBEB);

  static const Color lavender = Color(0xFF8B5CF6);
  static const Color lavenderBg = Color(0xFFF5F3FF);

  // Status Colors
  static const Color success = mint;
  static const Color successBg = mintBg;
  static const Color pending = amber;
  static const Color pendingBg = amberBg;
  static const Color warning = amber;
  static const Color danger = Color(0xFFFF4D4D);
  static const Color dangerBg = Color(0xFFFFEAEA);

  // Surfaces & Backgrounds
  static const Color background = Color(0xFFF8F9FD); // Soft tinted clean background
  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceSecondary = Color(0xFFF1F4F9);
  static const Color border = Color(0xFFEBEFF6);
  static const Color borderSubtle = Color(0xFFF0F3F8);

  // Text Colors
  static const Color textPrimary = Color(0xFF171A1F);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color textMuted = Color(0xFF9CA3AF);

  // Helper for Category Colors
  static ({Color fg, Color bg}) getCategoryColors(String category) {
    final cat = category.toLowerCase();
    if (cat.contains('bank')) {
      return (fg: primary, bg: primaryLight);
    } else if (cat.contains('card') || cat.contains('credit')) {
      return (fg: coral, bg: coralBg);
    } else if (cat.contains('loan')) {
      return (fg: amber, bg: amberBg);
    } else if (cat.contains('demat') || cat.contains('invest')) {
      return (fg: sky, bg: skyBg);
    } else if (cat.contains('ecommerce') || cat.contains('shop')) {
      return (fg: mint, bg: mintBg);
    } else {
      return (fg: lavender, bg: lavenderBg);
    }
  }

  // Helper for Status Colors
  static ({Color fg, Color bg}) getStatusColors(String status) {
    final st = status.toLowerCase();
    if (st.contains('approv') || st.contains('success') || st.contains('active')) {
      return (fg: success, bg: successBg);
    } else if (st.contains('pend') || st.contains('process')) {
      return (fg: pending, bg: pendingBg);
    } else if (st.contains('reject') || st.contains('fail') || st.contains('pause')) {
      return (fg: danger, bg: dangerBg);
    } else {
      return (fg: textSecondary, bg: surfaceSecondary);
    }
  }
}

class AppShadows {
  static final soft = [
    BoxShadow(
      color: const Color(0xFF2B3674).withValues(alpha: 0.05),
      blurRadius: 18,
      offset: const Offset(0, 4),
    ),
  ];

  static final card = [
    BoxShadow(
      color: const Color(0xFF1B2559).withValues(alpha: 0.04),
      blurRadius: 20,
      offset: const Offset(0, 6),
    ),
  ];

  static final primary = [
    BoxShadow(
      color: AppColors.primary.withValues(alpha: 0.35),
      blurRadius: 20,
      offset: const Offset(0, 8),
    ),
  ];

  static final floating = [
    BoxShadow(
      color: const Color(0xFF0D1B2A).withValues(alpha: 0.08),
      blurRadius: 24,
      offset: const Offset(0, 10),
    ),
  ];

  static final heroCard = [
    BoxShadow(
      color: AppColors.primary.withValues(alpha: 0.35),
      blurRadius: 24,
      offset: const Offset(0, 10),
    ),
  ];

  static List<BoxShadow> glow(Color color, {double alpha = 0.25}) => [
    BoxShadow(
      color: color.withValues(alpha: alpha),
      blurRadius: 12,
      offset: const Offset(0, 4),
    ),
  ];
}

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: AppColors.background,
      colorScheme: const ColorScheme.light(
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        surface: AppColors.surface,
        error: AppColors.danger,
      ),
      textTheme: GoogleFonts.plusJakartaSansTextTheme().copyWith(
        displayLarge: GoogleFonts.plusJakartaSans(
          fontSize: 32,
          fontWeight: FontWeight.w800,
          color: AppColors.textPrimary,
          letterSpacing: -0.5,
        ),
        headlineLarge: GoogleFonts.plusJakartaSans(
          fontSize: 24,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
          letterSpacing: -0.3,
        ),
        titleLarge: GoogleFonts.plusJakartaSans(
          fontSize: 18,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
        ),
        titleMedium: GoogleFonts.plusJakartaSans(
          fontSize: 16,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
        bodyLarge: GoogleFonts.plusJakartaSans(
          fontSize: 15,
          fontWeight: FontWeight.w500,
          color: AppColors.textPrimary,
        ),
        bodyMedium: GoogleFonts.plusJakartaSans(
          fontSize: 14,
          fontWeight: FontWeight.normal,
          color: AppColors.textSecondary,
        ),
        labelLarge: GoogleFonts.plusJakartaSans(
          fontSize: 14,
          fontWeight: FontWeight.w600,
          color: AppColors.textPrimary,
        ),
        bodySmall: GoogleFonts.plusJakartaSans(
          fontSize: 12,
          fontWeight: FontWeight.normal,
          color: AppColors.textMuted,
        ),
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        iconTheme: const IconThemeData(color: AppColors.textPrimary),
        titleTextStyle: GoogleFonts.plusJakartaSans(
          fontSize: 18,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
        ),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: AppColors.border, width: 1),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
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
          borderSide: const BorderSide(color: AppColors.primary, width: 1.8),
        ),
        labelStyle: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary, fontSize: 14),
        hintStyle: GoogleFonts.plusJakartaSans(color: AppColors.textMuted, fontSize: 14),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 22),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.plusJakartaSans(fontSize: 15, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}
