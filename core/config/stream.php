<?php

return [
    'default' => env('STREAM_PROVIDER', 'cloudflare'),

    'providers' => [
        'cloudflare' => [
            'account_id'      => env('CLOUDFLARE_ACCOUNT_ID'),
            'api_token'       => env('CLOUDFLARE_STREAM_API_TOKEN'),
            'signing_key_id'  => env('CLOUDFLARE_STREAM_SIGNING_KEY_ID'),
            'signing_key_pem' => env('CLOUDFLARE_STREAM_SIGNING_KEY_PEM'),
            'webhook_secret'  => env('CLOUDFLARE_STREAM_WEBHOOK_SECRET'),
        ],
    ],

    // Signed manifest URL lifetime. Long enough for a single watch session,
    // short enough that a leaked URL is low-value.
    'signed_url_ttl_seconds' => env('STREAM_SIGNED_URL_TTL', 14400),
];
