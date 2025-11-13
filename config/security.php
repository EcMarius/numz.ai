<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Configure password requirements for user accounts.
    |
    */

    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'max_length' => env('PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_number' => env('PASSWORD_REQUIRE_NUMBER', true),
        'require_special' => env('PASSWORD_REQUIRE_SPECIAL', true),
        'check_common' => env('PASSWORD_CHECK_COMMON', true),
        'check_sequential' => env('PASSWORD_CHECK_SEQUENTIAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session timeout and security settings.
    |
    */

    'session' => [
        'timeout' => env('SESSION_TIMEOUT', 1800), // 30 minutes in seconds
        'enforce_single' => env('SESSION_ENFORCE_SINGLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brute Force Protection
    |--------------------------------------------------------------------------
    |
    | Configure login attempt limits and lockout periods.
    |
    */

    'brute_force' => [
        'max_attempts' => env('BRUTE_FORCE_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('BRUTE_FORCE_DECAY_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist
    |--------------------------------------------------------------------------
    |
    | Configure IP addresses that are allowed to access admin areas.
    | Leave empty to allow all IPs.
    |
    */

    'ip_whitelist' => env('IP_WHITELIST') ? explode(',', env('IP_WHITELIST')) : [],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers for HTTP responses.
    |
    */

    'headers' => [
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],

        'x_frame_options' => [
            'enabled' => env('SECURITY_X_FRAME_OPTIONS_ENABLED', true),
            'value' => env('SECURITY_X_FRAME_OPTIONS_VALUE', 'SAMEORIGIN'),
        ],

        'x_content_type_options' => [
            'enabled' => env('SECURITY_X_CONTENT_TYPE_OPTIONS_ENABLED', true),
        ],

        'x_xss_protection' => [
            'enabled' => env('SECURITY_X_XSS_PROTECTION_ENABLED', true),
        ],

        'referrer_policy' => [
            'enabled' => env('SECURITY_REFERRER_POLICY_ENABLED', true),
            'value' => env('SECURITY_REFERRER_POLICY_VALUE', 'strict-origin-when-cross-origin'),
        ],

        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', false),
            'directives' => [
                'default-src' => ["'self'"],
                'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
                'style-src' => ["'self'", "'unsafe-inline'"],
                'img-src' => ["'self'", 'data:', 'https:'],
                'font-src' => ["'self'", 'data:'],
                'connect-src' => ["'self'"],
                'frame-ancestors' => ["'self'"],
            ],
        ],

        'permissions_policy' => [
            'enabled' => env('SECURITY_PERMISSIONS_POLICY_ENABLED', true),
            'directives' => [
                'geolocation' => 'self',
                'microphone' => 'none',
                'camera' => 'none',
                'payment' => 'self',
                'usb' => 'none',
                'magnetometer' => 'none',
                'gyroscope' => 'none',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configure 2FA enforcement settings.
    |
    */

    '2fa' => [
        'enforce' => env('2FA_ENFORCE', false),
        'enforce_for_roles' => env('2FA_ENFORCE_FOR_ROLES') ? explode(',', env('2FA_ENFORCE_FOR_ROLES')) : [],
    ],

];
