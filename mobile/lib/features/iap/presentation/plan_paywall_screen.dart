import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../api/api_exception.dart';
import '../data/iap_repository.dart';
import '../domain/plan_preview.dart';

final _planProvider = FutureProvider.autoDispose.family<PlanPreview, String>(
  (ref, slug) => ref.read(iapRepositoryProvider).planBySlug(slug),
);

class PlanPaywallScreen extends ConsumerStatefulWidget {
  const PlanPaywallScreen({required this.slug, super.key});
  final String slug;

  @override
  ConsumerState<PlanPaywallScreen> createState() => _PlanPaywallScreenState();
}

class _PlanPaywallScreenState extends ConsumerState<PlanPaywallScreen> {
  bool _busy = false;

  Future<void> _subscribe(PlanPreview plan) async {
    setState(() => _busy = true);
    try {
      final result = await ref.read(iapRepositoryProvider).purchaseAndVerifyPlan(plan);
      if (!mounted) return;
      if (result.unlocked) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Subscription active.')),
        );
        Navigator.of(context).pop(true);
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Purchase recorded; entitlement pending.')),
        );
      }
    } on ApiException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final planAsync = ref.watch(_planProvider(widget.slug));

    return Scaffold(
      appBar: AppBar(title: const Text('Subscribe')),
      body: planAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              e is ApiException ? e.message : 'Could not load this plan.',
              textAlign: TextAlign.center,
            ),
          ),
        ),
        data: (plan) {
          final price = plan.iap?.mobilePriceUsd ?? plan.priceUsd;
          final available = plan.storeProductId != null;

          return SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(plan.name, style: Theme.of(context).textTheme.headlineSmall),
                  if (plan.creatorName != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Text(
                        'By ${plan.creatorName}',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ),
                  const SizedBox(height: 24),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '\$${price.toStringAsFixed(2)} / month',
                            style: Theme.of(context).textTheme.titleLarge,
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              const Icon(Icons.play_circle_outline, size: 20),
                              const SizedBox(width: 8),
                              Text('${plan.videoCount} videos'),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              const Icon(Icons.queue_play_next_outlined, size: 20),
                              const SizedBox(width: 8),
                              Text('${plan.playlistCount} playlists'),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            'Renews monthly. Cancel anytime from your platform subscription settings.',
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                  ),
                  const Spacer(),
                  if (!available)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Text(
                        'This plan is not available on mobile yet.',
                        style: TextStyle(color: Theme.of(context).colorScheme.error),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  FilledButton(
                    onPressed: (_busy || !available) ? null : () => _subscribe(plan),
                    child: _busy
                        ? const SizedBox.square(
                            dimension: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : Text(available ? 'Subscribe' : 'Unavailable'),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
