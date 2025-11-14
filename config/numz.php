<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */
    'invoice_prefix' => env('INVOICE_PREFIX', 'INV'),
    'currency' => env('DEFAULT_CURRENCY', 'USD'),
    'tax_rate' => env('DEFAULT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Billing Settings
    |--------------------------------------------------------------------------
    */
    'grace_period_days' => env('GRACE_PERIOD_DAYS', 7),
    'termination_days' => env('TERMINATION_DAYS', 30),
    'invoice_due_days' => env('INVOICE_DUE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Domain Registrar Settings
    |--------------------------------------------------------------------------
    */
    'domain_registrar' => env('DOMAIN_REGISTRAR', 'domainnameapi'),

    'registrars' => [
        'domainnameapi' => [
            'api_key' => env('DOMAINNAMEAPI_KEY'),
            'api_url' => env('DOMAINNAMEAPI_URL', 'https://api.domainnameapi.com/v1'),
        ],
        'namesilo' => [
            'api_key' => env('NAMESILO_API_KEY'),
            'api_url' => env('NAMESILO_API_URL', 'https://www.namesilo.com/api'),
        ],
        'namecheap' => [
            'api_key' => env('NAMECHEAP_API_KEY'),
            'api_user' => env('NAMECHEAP_API_USER'),
            'api_url' => env('NAMECHEAP_API_URL', 'https://api.namecheap.com/xml.response'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit_enabled' => env('AUDIT_LOGGING_ENABLED', true),
    'audit_retention_days' => env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    */
    'send_invoice_emails' => env('SEND_INVOICE_EMAILS', true),
    'send_renewal_reminders' => env('SEND_RENEWAL_REMINDERS', true),
    'renewal_reminder_days' => [60, 30, 14, 7, 3, 1],

    /*
    |--------------------------------------------------------------------------
    | Provisioning Settings
    |--------------------------------------------------------------------------
    */
    'auto_provision' => env('AUTO_PROVISION_SERVICES', true),
    'provision_retry_attempts' => env('PROVISION_RETRY_ATTEMPTS', 3),
    'provision_retry_delay' => env('PROVISION_RETRY_DELAY', 5), // minutes

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'encrypt_passwords' => env('ENCRYPT_SERVICE_PASSWORDS', true),
    'password_min_length' => env('PASSWORD_MIN_LENGTH', 12),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'webhooks' => env('FEATURE_WEBHOOKS', true),
        'marketplace' => env('FEATURE_MARKETPLACE', true),
        'growth_hacking' => env('FEATURE_GROWTH_HACKING', true),
        'api_access' => env('FEATURE_API_ACCESS', true),
    ],
];
