import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../api/api_exception.dart';
import '../data/wallet_repository.dart';
import '../domain/transaction.dart';

final _transactionsControllerProvider =
    StateNotifierProvider.autoDispose<_TransactionsController, _TransactionsState>(
  (ref) => _TransactionsController(ref.read(walletRepositoryProvider))..load(),
);

class _TransactionsController extends StateNotifier<_TransactionsState> {
  _TransactionsController(this._repo) : super(const _TransactionsState());
  final WalletRepository _repo;

  Future<void> load() async {
    state = state.copyWith(isLoading: true, items: const [], page: 1);
    try {
      final page = await _repo.transactions(type: state.filter);
      state = state.copyWith(
        items: page.items,
        page: page.page,
        lastPage: page.lastPage,
        isLoading: false,
      );
    } on ApiException catch (e) {
      state = state.copyWith(isLoading: false, error: e.message);
    }
  }

  Future<void> loadMore() async {
    if (state.isLoadingMore || state.page >= state.lastPage) return;
    state = state.copyWith(isLoadingMore: true);
    try {
      final next = await _repo.transactions(type: state.filter, page: state.page + 1);
      state = state.copyWith(
        items: [...state.items, ...next.items],
        page: next.page,
        lastPage: next.lastPage,
        isLoadingMore: false,
      );
    } on ApiException catch (e) {
      state = state.copyWith(isLoadingMore: false, error: e.message);
    }
  }

  Future<void> setFilter(String? filter) async {
    if (filter == state.filter) return;
    state = state.copyWith(filter: filter, clearError: true);
    await load();
  }
}

class _TransactionsState {
  const _TransactionsState({
    this.items = const [],
    this.page = 1,
    this.lastPage = 1,
    this.isLoading = false,
    this.isLoadingMore = false,
    this.filter,
    this.error,
  });

  final List<Transaction> items;
  final int page;
  final int lastPage;
  final bool isLoading;
  final bool isLoadingMore;
  final String? filter;
  final String? error;

  _TransactionsState copyWith({
    List<Transaction>? items,
    int? page,
    int? lastPage,
    bool? isLoading,
    bool? isLoadingMore,
    String? filter,
    String? error,
    bool clearError = false,
  }) {
    return _TransactionsState(
      items: items ?? this.items,
      page: page ?? this.page,
      lastPage: lastPage ?? this.lastPage,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      filter: filter,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class TransactionsScreen extends ConsumerStatefulWidget {
  const TransactionsScreen({super.key});

  @override
  ConsumerState<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends ConsumerState<TransactionsScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
        ref.read(_transactionsControllerProvider.notifier).loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  static const _filters = [
    (null, 'All'),
    ('deposit', 'Deposits'),
    ('purchase', 'Purchases'),
    ('earning', 'Earnings'),
    ('withdraw', 'Withdrawals'),
  ];

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(_transactionsControllerProvider);
    final controller = ref.read(_transactionsControllerProvider.notifier);

    return Scaffold(
      appBar: AppBar(title: const Text('Transactions')),
      body: Column(
        children: [
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Row(
              children: [
                for (final f in _filters)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(f.$2),
                      selected: state.filter == f.$1,
                      onSelected: (_) => controller.setFilter(f.$1),
                    ),
                  ),
              ],
            ),
          ),
          Expanded(child: _body(context, state)),
        ],
      ),
    );
  }

  Widget _body(BuildContext context, _TransactionsState state) {
    if (state.isLoading && state.items.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (state.error != null && state.items.isEmpty) {
      return Center(child: Text(state.error!));
    }
    if (state.items.isEmpty) {
      return const Center(child: Text('No transactions yet.'));
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(_transactionsControllerProvider.notifier).load(),
      child: ListView.separated(
        controller: _scroll,
        itemCount: state.items.length + (state.page < state.lastPage ? 1 : 0),
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (_, i) {
          if (i >= state.items.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
            );
          }
          return _TransactionTile(transaction: state.items[i]);
        },
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  const _TransactionTile({required this.transaction});
  final Transaction transaction;

  @override
  Widget build(BuildContext context) {
    final color = transaction.isCredit
        ? Colors.greenAccent
        : Theme.of(context).colorScheme.error;
    return ListTile(
      leading: Icon(
        transaction.isCredit ? Icons.arrow_downward : Icons.arrow_upward,
        color: color,
      ),
      title: Text(
        transaction.details.isEmpty ? transaction.remark : transaction.details,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(
        DateFormat.yMMMd().add_jm().format(transaction.createdAt),
        style: Theme.of(context).textTheme.bodySmall,
      ),
      trailing: Text(
        '${transaction.isCredit ? '+' : '-'}${transaction.amount.toStringAsFixed(2)}',
        style: TextStyle(color: color, fontWeight: FontWeight.w600),
      ),
    );
  }
}
