<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iap_products', function (Blueprint $table) {
            $table->id();

            // Internal item this maps to. Exactly one of (plan_id, video_id, playlist_id) is set.
            $table->string('type'); // 'plan' | 'video' | 'playlist'
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->unsignedBigInteger('video_id')->nullable()->index();
            $table->unsignedBigInteger('playlist_id')->nullable()->index();

            // Store product identifiers as registered in App Store Connect / Play Console.
            $table->string('apple_product_id')->nullable()->index();
            $table->string('google_product_id')->nullable()->index();

            // Apple/Google have fixed pricing tiers. We store the *display* price
            // for parity with web; the actual charge is whatever the store tier shows.
            $table->decimal('price_usd_web', 12, 2)->default(0);
            $table->decimal('price_usd_mobile', 12, 2)->default(0);

            // Subscription vs one-time. Auto-renewable plans require webhook handling.
            $table->boolean('is_subscription')->default(false);
            $table->unsignedInteger('subscription_period_days')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iap_products');
    }
};
