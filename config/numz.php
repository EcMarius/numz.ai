<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NUMZ.AI - The First AI Hosting Billing Software
    |--------------------------------------------------------------------------
    */

    'name' => 'NUMZ.AI',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', true),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', true),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
        ],
        'paysafecard' => [
            'enabled' => env('PAYSAFECARD_ENABLED', false),
            'api_key' => env('PAYSAFECARD_API_KEY'),
            'test_mode' => env('PAYSAFECARD_TEST_MODE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Registrars
    |--------------------------------------------------------------------------
    */
    'registrars' => [
        'domainnameapi' => [
            'enabled' => env('DOMAINNAMEAPI_ENABLED', true),
            'username' => env('DOMAINNAMEAPI_USERNAME'),
            'password' => env('DOMAINNAMEAPI_PASSWORD'),
            'test_mode' => env('DOMAINNAMEAPI_TEST_MODE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hosting Provisioning
    |--------------------------------------------------------------------------
    */
    'provisioning' => [
        'oneprovider' => [
            'enabled' => env('ONEPROVIDER_ENABLED', false),
            'api_key' => env('ONEPROVIDER_API_KEY'),
        ],
        'cpanel' => [
            'enabled' => env('CPANEL_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'tawkto' => [
            'enabled' => env('TAWKTO_ENABLED', false),
            'property_id' => env('TAWKTO_PROPERTY_ID'),
            'widget_id' => env('TAWKTO_WIDGET_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Settings
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'currency' => env('NUMZ_CURRENCY', 'USD'),
        'invoice_prefix' => env('NUMZ_INVOICE_PREFIX', 'INV'),
        'due_days' => env('NUMZ_INVOICE_DUE_DAYS', 14),
        'auto_suspend_days' => env('NUMZ_AUTO_SUSPEND_DAYS', 7),
        'auto_terminate_days' => env('NUMZ_AUTO_TERMINATE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | WHMCS Compatibility
    |--------------------------------------------------------------------------
    */
    'whmcs_compatibility' => env('NUMZ_WHMCS_COMPAT', true),
];
