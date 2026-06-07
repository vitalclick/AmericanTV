<?php

return [
    // Method codes used to flag deposits coming from native app IAP.
    // Re-uses the >=5000 "mobile" reservation already in Deposit::methodName().
    'method_code' => [
        'apple'  => 5001,
        'google' => 5002,
    ],

    'apple' => [
        'bundle_id'   => env('IAP_APPLE_BUNDLE_ID'),
        'issuer_id'   => env('IAP_APPLE_ISSUER_ID'),
        'key_id'      => env('IAP_APPLE_KEY_ID'),
        'private_key' => env('IAP_APPLE_PRIVATE_KEY'),
        'environment' => env('IAP_APPLE_ENVIRONMENT', 'production'),
    ],

    'google' => [
        'package_name'         => env('IAP_GOOGLE_PACKAGE_NAME'),
        'service_account_json' => env('IAP_GOOGLE_SERVICE_ACCOUNT_JSON'),
    ],

    'revenuecat' => [
        // Shared secret RevenueCat sends in the Authorization header on every
        // webhook delivery. Configure in the RevenueCat dashboard -> Webhooks.
        'webhook_auth_header' => env('REVENUECAT_WEBHOOK_AUTH_HEADER'),
    ],
];
