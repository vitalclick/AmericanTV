/// Mirrors Transaction in core/docs/api/openapi-v1.yaml.
class Transaction {
  const Transaction({
    required this.id,
    required this.trx,
    required this.trxType,
    required this.amount,
    required this.postBalance,
    required this.details,
    required this.remark,
    required this.createdAt,
    this.charge = 0,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) {
    return Transaction(
      id: json['id'] as int,
      trx: json['trx'] as String? ?? '',
      trxType: json['trx_type'] as String? ?? '+',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      charge: (json['charge'] as num?)?.toDouble() ?? 0,
      postBalance: (json['post_balance'] as num?)?.toDouble() ?? 0,
      details: json['details'] as String? ?? '',
      remark: json['remark'] as String? ?? '',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  final int id;
  final String trx;
  final String trxType; // '+' or '-'
  final double amount;
  final double charge;
  final double postBalance;
  final String details;
  final String remark;
  final DateTime createdAt;

  bool get isCredit => trxType == '+';
}
