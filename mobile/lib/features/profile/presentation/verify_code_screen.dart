import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../../auth/application/auth_controller.dart';
import '../data/verification_repository.dart';

enum VerifyKind { email, mobile }

/// Send-code-then-enter-code screen reused for both email + mobile
/// verification. Posts to /auth/{email|mobile}/send on entry, surfaces a
/// "Resend in Ns" countdown, then verifies the entered code.
class VerifyCodeScreen extends ConsumerStatefulWidget {
  const VerifyCodeScreen({required this.kind, super.key});
  final VerifyKind kind;

  @override
  ConsumerState<VerifyCodeScreen> createState() => _VerifyCodeScreenState();
}

class _VerifyCodeScreenState extends ConsumerState<VerifyCodeScreen> {
  final _codeController = TextEditingController();
  bool _busy = false;
  bool _sending = false;
  int _resendIn = 0;
  Timer? _countdown;
  String? _error;

  @override
  void initState() {
    super.initState();
    // Send the code immediately on entry. If the rate-limit kicks in we
    // surface that and start the countdown using the server's retry_after_s.
    WidgetsBinding.instance.addPostFrameCallback((_) => _send());
  }

  @override
  void dispose() {
    _countdown?.cancel();
    _codeController.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    if (_sending) return;
    setState(() {
      _sending = true;
      _error = null;
    });
    try {
      final repo = ref.read(verificationRepositoryProvider);
      if (widget.kind == VerifyKind.email) {
        await repo.sendEmailCode();
      } else {
        await repo.sendMobileCode();
      }
      _startCountdown(120);
    } on ApiException catch (e) {
      // 429 returns retry_after_s; we don't surface that field through
      // ApiException today so fall back to the default 2-minute window.
      setState(() => _error = e.message);
      _startCountdown(120);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  void _startCountdown(int seconds) {
    _countdown?.cancel();
    setState(() => _resendIn = seconds);
    _countdown = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      setState(() => _resendIn = (_resendIn - 1).clamp(0, 999));
      if (_resendIn == 0) _countdown?.cancel();
    });
  }

  Future<void> _verify() async {
    final code = _codeController.text.trim();
    if (code.isEmpty) return;

    setState(() {
      _busy = true;
      _error = null;
    });

    try {
      final repo = ref.read(verificationRepositoryProvider);
      if (widget.kind == VerifyKind.email) {
        await repo.verifyEmail(code);
      } else {
        await repo.verifyMobile(code);
      }
      // Refresh the cached User so the verification badge in ProfileScreen
      // disappears without a manual reload.
      await ref.read(authControllerProvider.notifier).bootstrap();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(widget.kind == VerifyKind.email
                ? 'Email verified.'
                : 'Mobile verified.'),
          ),
        );
        Navigator.of(context).pop();
      }
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEmail = widget.kind == VerifyKind.email;
    final user = ref.watch(authControllerProvider).user;

    return Scaffold(
      appBar: AppBar(title: Text(isEmail ? 'Verify email' : 'Verify mobile')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Icon(
                isEmail ? Icons.mark_email_unread_outlined : Icons.sms_outlined,
                size: 56,
                color: Theme.of(context).colorScheme.primary,
              ),
              const SizedBox(height: 16),
              Text(
                isEmail
                    ? "We've sent a 6-digit code to ${user?.email ?? 'your email'}."
                    : "We've sent a 6-digit code to your mobile.",
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyLarge,
              ),
              const SizedBox(height: 24),
              TextField(
                controller: _codeController,
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(6),
                ],
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.headlineSmall,
                decoration: const InputDecoration(
                  hintText: '••••••',
                  counterText: '',
                ),
                onSubmitted: (_) => _verify(),
              ),
              if (_error != null) ...[
                const SizedBox(height: 8),
                Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
              ],
              const SizedBox(height: 24),
              FilledButton(
                onPressed: _busy ? null : _verify,
                child: _busy
                    ? const SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Verify'),
              ),
              const SizedBox(height: 12),
              TextButton(
                onPressed: (_sending || _resendIn > 0) ? null : _send,
                child: Text(
                  _resendIn > 0
                      ? 'Resend in ${_resendIn}s'
                      : (_sending ? 'Sending…' : 'Resend code'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
