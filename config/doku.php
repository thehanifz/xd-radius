<?php

return [
    'enabled' => (bool) env('DOKU_ENABLED', false),
    'environment' => env('DOKU_ENVIRONMENT', 'sandbox'),
    'base_url' => env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
    'client_id' => env('DOKU_CLIENT_ID'),
    'secret_key' => env('DOKU_SECRET_KEY'),
    'api_key' => env('DOKU_API_KEY'),
    'doku_public_key' => env('DOKU_PUBLIC_KEY'),
    'private_key_path' => env('DOKU_PRIVATE_KEY_PATH'),
    'private_key_passphrase' => env('DOKU_PRIVATE_KEY_PASSPHRASE'),
    'merchant_id' => env('DOKU_MERCHANT_ID'),
    'terminal_id' => env('DOKU_TERMINAL_ID'),
    'channel_id' => env('DOKU_CHANNEL_ID', 'H2H'),
    'va_bank' => env('DOKU_VA_BANK', 'BNI'),
    'va_partner_service_id' => env('DOKU_VA_PARTNER_SERVICE_ID'),
    'va_customer_prefix' => env('DOKU_VA_CUSTOMER_PREFIX', '3'),
    'notification_path' => env('DOKU_NOTIFICATION_PATH', '/webhooks/doku'),
    'timeout' => (int) env('DOKU_HTTP_TIMEOUT', 15),
];
