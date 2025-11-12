<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WHMCS Compatibility Mode
    |--------------------------------------------------------------------------
    |
    | Enable full backward compatibility with WHMCS modules and hooks
    |
    */
    'enabled' => env('WHMCS_COMPATIBILITY', true),

    /*
    |--------------------------------------------------------------------------
    | Module Directories
    |--------------------------------------------------------------------------
    |
    | Paths where WHMCS modules are located
    |
    */
    'module_paths' => [
        'servers' => base_path('modules/servers'),
        'addons' => base_path('modules/addons'),
        'gateways' => base_path('modules/gateways'),
        'registrars' => base_path('modules/registrars'),
        'fraud' => base_path('modules/fraud'),
        'notifications' => base_path('modules/notifications'),
        'widgets' => base_path('modules/widgets'),
        'mail' => base_path('modules/mail'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hook Directories
    |--------------------------------------------------------------------------
    |
    | Paths where WHMCS hooks are located
    |
    */
    'hook_paths' => [
        base_path('includes/hooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Active Addon Modules
    |--------------------------------------------------------------------------
    |
    | List of active addon modules
    |
    */
    'active_addons' => [
        // 'example_addon',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | WHMCS API configuration
    |
    */
    'api' => [
        'enabled' => env('WHMCS_API_ENABLED', true),
        'access_key' => env('WHMCS_API_ACCESS_KEY'),
        'secret_key' => env('WHMCS_API_SECRET_KEY'),
        'allowed_ips' => env('WHMCS_API_ALLOWED_IPS', ''),
        'rate_limit' => env('WHMCS_API_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix
    |--------------------------------------------------------------------------
    |
    | WHMCS table prefix (tbl by default)
    |
    */
    'table_prefix' => 'tbl',

    /*
    |--------------------------------------------------------------------------
    | Module Log
    |--------------------------------------------------------------------------
    |
    | Enable logging of module calls
    |
    */
    'module_log' => [
        'enabled' => env('WHMCS_MODULE_LOG', true),
        'log_requests' => true,
        'log_responses' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Template System
    |--------------------------------------------------------------------------
    |
    | Template engine settings (Smarty compatibility)
    |
    */
    'templates' => [
        'engine' => 'blade', // or 'smarty' for full compatibility
        'cache_enabled' => env('WHMCS_TEMPLATE_CACHE', true),
        'compile_check' => env('APP_DEBUG', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cron Settings
    |--------------------------------------------------------------------------
    |
    | WHMCS cron job configuration
    |
    */
    'cron' => [
        'enabled' => true,
        'key' => env('WHMCS_CRON_KEY'),
        'ip_whitelist' => env('WHMCS_CRON_IPS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency and formatting
    |
    */
    'currency' => [
        'default' => 'USD',
        'prefix' => '$',
        'suffix' => '',
        'format' => '1',
        'decimals' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Date Format
    |--------------------------------------------------------------------------
    |
    | Default date format for WHMCS compatibility
    |
    */
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i:s',

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    |
    | Various WHMCS system settings
    |
    */
    'system' => [
        'company_name' => env('WHMCS_COMPANY_NAME', config('app.name')),
        'system_url' => env('APP_URL'),
        'charset' => 'UTF-8',
        'license_key' => env('WHMCS_LICENSE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoicing Settings
    |--------------------------------------------------------------------------
    |
    | Invoice generation and payment configuration
    |
    */
    'invoicing' => [
        'grace_days' => env('WHMCS_INVOICE_GRACE_DAYS', 7),
        'reminder_days' => [7, 3, 1], // Days before due date to send reminders
        'auto_create' => env('WHMCS_AUTO_CREATE_INVOICES', true),
        'overdue_notice_days' => [1, 3, 7], // Days after due date
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Settings
    |--------------------------------------------------------------------------
    |
    | Domain registration and renewal configuration
    |
    */
    'domains' => [
        'auto_renew' => env('WHMCS_AUTO_RENEW_DOMAINS', true),
        'sync_enabled' => env('WHMCS_DOMAIN_SYNC', true),
        'sync_interval' => 24, // hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    |
    | Tax calculation configuration
    |
    */
    'tax' => [
        'enabled' => env('WHMCS_TAX_ENABLED', false),
        'rate' => env('WHMCS_TAX_RATE', 0),
        'name' => env('WHMCS_TAX_NAME', 'VAT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provisioning Settings
    |--------------------------------------------------------------------------
    |
    | Automatic provisioning configuration
    |
    */
    'provisioning' => [
        'auto_create' => env('WHMCS_AUTO_PROVISION', true),
        'auto_suspend' => env('WHMCS_AUTO_SUSPEND', true),
        'auto_terminate' => env('WHMCS_AUTO_TERMINATE', false),
        'suspension_grace_days' => env('WHMCS_SUSPENSION_GRACE', 3),
        'termination_days' => env('WHMCS_TERMINATION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Email notification configuration
    |
    */
    'email' => [
        'from_name' => env('WHMCS_EMAIL_FROM_NAME', config('app.name')),
        'from_email' => env('WHMCS_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
        'send_account_emails' => true,
        'send_product_emails' => true,
        'send_domain_emails' => true,
        'send_invoice_emails' => true,
        'send_support_emails' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Area Settings
    |--------------------------------------------------------------------------
    |
    | Client area configuration
    |
    */
    'client_area' => [
        'page_title' => env('WHMCS_PAGE_TITLE', config('app.name') . ' - Client Area'),
        'allow_registration' => true,
        'require_email_verification' => true,
        'default_theme' => 'six',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Area Settings
    |--------------------------------------------------------------------------
    |
    | Admin area configuration
    |
    */
    'admin_area' => [
        'path' => 'admin',
        'session_timeout' => 3600,
        'ip_whitelist' => env('WHMCS_ADMIN_IPS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration
    |
    */
    'security' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'sql_injection_protection' => true,
        'password_strength' => env('WHMCS_PASSWORD_STRENGTH', 80),
        'two_factor_auth' => env('WHMCS_2FA_ENABLED', true),
    ],
];
