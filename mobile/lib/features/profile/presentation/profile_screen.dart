import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/application/auth_controller.dart';
import 'change_password_screen.dart';
import 'edit_profile_screen.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider).user;
    if (user == null) return const SizedBox.shrink();

    return ListView(
      children: [
        const SizedBox(height: 16),
        CircleAvatar(
          radius: 48,
          backgroundColor: Theme.of(context).colorScheme.primaryContainer,
          child: Text(
            user.displayName.isNotEmpty ? user.displayName[0].toUpperCase() : '?',
            style: const TextStyle(fontSize: 32),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child: Text(user.displayName, style: Theme.of(context).textTheme.titleLarge),
        ),
        Center(
          child: Text(user.email, style: Theme.of(context).textTheme.bodyMedium),
        ),
        if (!user.emailVerified)
          const Padding(
            padding: EdgeInsets.only(top: 8),
            child: Center(child: _Pill(label: 'Email not verified')),
          ),
        const SizedBox(height: 24),
        const _SectionLabel('Account'),
        ListTile(
          leading: const Icon(Icons.person_outline),
          title: const Text('Edit profile'),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(builder: (_) => const EditProfileScreen()),
          ),
        ),
        ListTile(
          leading: const Icon(Icons.lock_outline),
          title: const Text('Change password'),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(builder: (_) => const ChangePasswordScreen()),
          ),
        ),
        const _SectionLabel('Wallet'),
        ListTile(
          leading: const Icon(Icons.account_balance_wallet_outlined),
          title: const Text('Balance'),
          trailing: Text('\$${user.balance.toStringAsFixed(2)}'),
        ),
        const SizedBox(height: 16),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          child: OutlinedButton.icon(
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
            icon: const Icon(Icons.logout),
            label: const Text('Sign out'),
          ),
        ),
      ],
    );
  }
}

class _SectionLabel extends StatelessWidget {
  const _SectionLabel(this.label);
  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        label.toUpperCase(),
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              letterSpacing: 1.2,
              color: Theme.of(context).disabledColor,
            ),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.errorContainer,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(color: Theme.of(context).colorScheme.onErrorContainer, fontSize: 12),
      ),
    );
  }
}
