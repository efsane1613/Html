<?php
$config = [
    'app_name' => 'Google Review Bot',
    'base_url' => '/public',
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'google_review_bot',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4'
    ],
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'your-google-client-id',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'your-google-client-secret',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/public/google_callback.php',
        'scope' => 'https://www.googleapis.com/auth/business.manage',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token'
    ],
    'payments' => [
        'stripe_secret' => getenv('STRIPE_SECRET') ?: 'your-stripe-secret',
        'stripe_publishable' => getenv('STRIPE_PUBLISHABLE') ?: 'your-stripe-publishable',
        'paypal_client_id' => getenv('PAYPAL_CLIENT_ID') ?: 'your-paypal-client-id',
        'paypal_secret' => getenv('PAYPAL_SECRET') ?: 'your-paypal-secret',
        'currency' => 'EUR'
    ],
    'ai' => [
        'provider' => 'gemini',
        'api_key' => getenv('GEMINI_API_KEY') ?: 'your-gemini-api-key',
        'model' => 'gemini-1.5-pro'
    ]
];
return $config;
