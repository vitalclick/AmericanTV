<?php

namespace Database\Seeders;

use App\Models\IapProduct;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds App Store / Play Console product mappings for every existing Plan
 * row. Run after registering the corresponding product identifiers in
 * App Store Connect (Auto-Renewable Subscriptions) and Play Console
 * (Subscriptions).
 *
 * Product ID convention: <bundle>.plan.<plan_slug>.monthly
 *
 *   php artisan db:seed --class=IapProductSeeder
 */
class IapProductSeeder extends Seeder
{
    public function run(): void
    {
        $bundle = (string) config('iap.apple.bundle_id', 'com.americantv');

        Plan::where('status', 1)->each(function (Plan $plan) use ($bundle) {
            $productId = "{$bundle}.plan.{$plan->slug}.monthly";

            IapProduct::updateOrCreate(
                ['type' => 'plan', 'plan_id' => $plan->id],
                [
                    'apple_product_id'         => $productId,
                    'google_product_id'        => $productId,
                    'price_usd_web'            => $plan->price,
                    'price_usd_mobile'         => $plan->price, // adjust upward to absorb store cut.
                    'is_subscription'          => true,
                    'subscription_period_days' => 30,
                    'active'                   => true,
                ],
            );
        });
    }
}
