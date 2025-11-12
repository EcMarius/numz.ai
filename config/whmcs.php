<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WHMCS Compatibility Mode
    |--------------------------------------------------------------------------
    |
    | WHMCS compatibility is ALWAYS enabled to support WHMCS modules,
    | themes, templates, and plugins out of the box.
    |
    | All settings are managed via Admin Panel > Settings > WHMCS Configuration
    | Settings are stored in database and can be changed without touching code.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Module Directories
    |--------------------------------------------------------------------------
    |
    | Paths where WHMCS modules are located. These match WHMCS conventions.
    |
    */
    'module_paths' => [
        'servers' => base_path('modules/servers'),          // Provisioning modules (cPanel, Plesk, etc.)
        'addons' => base_path('modules/addons'),            // Addon modules
        'gateways' => base_path('modules/gateways'),        // Payment gateways (Stripe, PayPal, etc.)
        'registrars' => base_path('modules/registrars'),    // Domain registrars
        'fraud' => base_path('modules/fraud'),              // Fraud detection modules
        'notifications' => base_path('modules/notifications'), // Notification providers
        'widgets' => base_path('modules/widgets'),          // Dashboard widgets
        'mail' => base_path('modules/mail'),                // Mail providers
    ],

    /*
    |--------------------------------------------------------------------------
    | Hook Directories
    |--------------------------------------------------------------------------
    |
    | Paths where WHMCS hook files are located
    |
    */
    'hook_paths' => [
        base_path('includes/hooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Directories
    |--------------------------------------------------------------------------
    |
    | Paths where WHMCS themes and templates are located
    |
    */
    'template_paths' => [
        base_path('templates'),                              // Main templates directory
        base_path('templates/orderforms'),                   // Order form templates
        base_path('templates/invoices'),                     // Invoice templates
        base_path('templates/emails'),                       // Email templates
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix
    |--------------------------------------------------------------------------
    |
    | WHMCS table prefix (tbl by default) - used for database views
    |
    */
    'table_prefix' => 'tbl',

    /*
    |--------------------------------------------------------------------------
    | Module Logging
    |--------------------------------------------------------------------------
    |
    | Logging of module calls for debugging
    | Can be toggled per-module in admin panel
    |
    */
    'module_log' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors_only' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Template System
    |--------------------------------------------------------------------------
    |
    | WHMCS uses Smarty templates. We support both Smarty and Blade.
    | Smarty templates (.tpl) are automatically converted to Blade.
    |
    */
    'templates' => [
        'engine' => 'smarty',                                // 'smarty' or 'blade'
        'cache_enabled' => !config('app.debug'),
        'compile_check' => config('app.debug'),
        'smarty_compat' => true,                             // Auto-convert Smarty syntax
    ],

    /*
    |--------------------------------------------------------------------------
    | Cron Settings
    |--------------------------------------------------------------------------
    |
    | WHMCS cron job configuration
    | Key is auto-generated and stored in database
    |
    */
    'cron' => [
        'enabled' => true,
        'key' => null,                                       // Set in admin panel
        'ip_whitelist' => [],                                // Set in admin panel
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These are default values. Actual settings are stored in database
    | and can be changed via Admin Panel > Settings > WHMCS Configuration
    |
    */
    'defaults' => [
        // Currency
        'currency' => [
            'default' => 'USD',
            'prefix' => '$',
            'suffix' => '',
            'format' => 1,
            'decimals' => 2,
        ],

        // Date formats
        'date_format' => 'd/m/Y',
        'datetime_format' => 'd/m/Y H:i:s',

        // Invoicing
        'invoicing' => [
            'grace_days' => 7,                               // Days before service suspension
            'reminder_days' => [7, 3, 1],                    // Days before due date
            'auto_create' => true,                           // Auto-generate recurring invoices
            'overdue_notice_days' => [1, 3, 7],             // Days after due date
        ],

        // Domains
        'domains' => [
            'auto_renew' => false,                           // Default auto-renew off
            'sync_enabled' => true,                          // Sync with registrar
            'sync_interval' => 24,                           // Hours between syncs
        ],

        // Tax
        'tax' => [
            'enabled' => false,
            'rate' => 0,
            'name' => 'VAT',
            'inclusive' => false,
        ],

        // Provisioning
        'provisioning' => [
            'auto_create' => true,                           // Auto-provision on payment
            'auto_suspend' => true,                          // Auto-suspend when overdue
            'auto_terminate' => false,                       // Auto-terminate after suspension
            'suspension_grace_days' => 3,                    // Days after due date before suspension
            'termination_days' => 30,                        // Days suspended before termination
        ],

        // Email
        'email' => [
            'from_name' => config('app.name'),
            'from_email' => config('mail.from.address'),
            'send_account_emails' => true,
            'send_product_emails' => true,
            'send_domain_emails' => true,
            'send_invoice_emails' => true,
            'send_support_emails' => true,
        ],

        // Client Area
        'client_area' => [
            'page_title' => config('app.name') . ' - Client Area',
            'allow_registration' => true,
            'require_email_verification' => true,
            'default_theme' => 'twenty-one',                 // WHMCS default theme
        ],

        // Security
        'security' => [
            'csrf_protection' => true,
            'xss_protection' => true,
            'password_min_strength' => 60,                   // 0-100 scale
            'two_factor_enabled' => false,                   // Off by default
            'session_timeout' => 3600,                       // 1 hour
        ],
    ],
];
