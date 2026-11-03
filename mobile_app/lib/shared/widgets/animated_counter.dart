import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

/// Smooth animated count-up number text widget
class AnimatedCounter extends StatelessWidget {
  final double value;
  final TextStyle? style;
  final String prefix;
  final String suffix;
  final int decimalDigits;
  final Duration duration;
  final Curve curve;

  const AnimatedCounter({
    super.key,
    required this.value,
    this.style,
    this.prefix = '',
    this.suffix = '',
    this.decimalDigits = 0,
    this.duration = const Duration(milliseconds: 900),
    this.curve = Curves.easeOutCubic,
  });

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween<double>(begin: 0, end: value),
      duration: duration,
      curve: curve,
      builder: (context, val, child) {
        final formatter = NumberFormat.currency(
          symbol: prefix,
          decimalDigits: decimalDigits,
        );
        final formattedText = '${formatter.format(val)}$suffix';
        return Text(
          formattedText,
          style: style,
        );
      },
    );
  }
}
