<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            // Apple originalTransactionId or Google purchaseToken.
            // Indexed for idempotency lookups during receipt verification.
            $table->string('iap_transaction_id')->nullable()->after('trx')->index();

            // Raw verified receipt payload kept for audit + dispute response.
            $table->text('iap_receipt')->nullable()->after('iap_transaction_id');

            // Apple/Google product identifier captured at purchase time.
            $table->string('iap_product_id')->nullable()->after('iap_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropColumn(['iap_transaction_id', 'iap_receipt', 'iap_product_id']);
        });
    }
};
