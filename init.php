<?php
/**
 * WHMCS Compatibility Initialization
 *
 * This file bootstraps Laravel for WHMCS module compatibility
 * WHMCS modules expect to include init.php to access system functions
 */

// Bootstrap Laravel
require_once __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Create kernel and handle request context
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Load WHMCS compatibility functions
require_once __DIR__ . '/includes/whmcs_compat.php';
require_once __DIR__ . '/includes/whmcs_functions.php';

// Define WHMCS constant if not already defined
if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Set up WHMCS-style global arrays (for compatibility)
$CONFIG = config('whmcs');
$LANG = [];

// Legacy timezone handling
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(config('app.timezone', 'UTC'));
}

// Set error reporting based on environment
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
}

// Session handling (if needed by module)
if (!session_id() && !headers_sent()) {
    session_start();
}

// Make common Laravel facades available globally
class_alias('Illuminate\Support\Facades\DB', 'DB');
class_alias('Illuminate\Support\Facades\Log', 'Log');
class_alias('Illuminate\Support\Facades\Cache', 'Cache');
class_alias('Illuminate\Support\Facades\Config', 'Config');

// WHMCS system URL and paths
if (!defined('ROOTDIR')) {
    define('ROOTDIR', base_path());
}

// Set system URL
$systemUrl = config('app.url');
if (!str_ends_with($systemUrl, '/')) {
    $systemUrl .= '/';
}

if (!defined('SYSTEMURL')) {
    define('SYSTEMURL', $systemUrl);
}

// Currency data
$currency = [
    'id' => 1,
    'code' => \App\Numz\WHMCS\Settings::get('currency_default', 'USD'),
    'prefix' => \App\Numz\WHMCS\Settings::get('currency_prefix', '$'),
    'suffix' => \App\Numz\WHMCS\Settings::get('currency_suffix', ''),
    'format' => 1,
    'rate' => '1.00000',
];
