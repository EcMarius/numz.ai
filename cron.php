<?php
/**
 * WHMCS-Compatible Cron File
 *
 * This file provides backward compatibility with WHMCS cron setup
 * It bootstraps Laravel and runs the WHMCS cron command
 *
 * Setup as cron job:
 * */5 * * * * php /path/to/cron.php > /dev/null 2>&1
 * OR
 * */5 * * * * wget -q -O- http://yourdomain.com/cron.php?cron_key=YOUR_KEY
 *
 * The cron key is configured in Admin Panel > Settings > WHMCS Settings > Cron tab
 */

// Bootstrap Laravel
require __DIR__ . '/init.php';

// Prevent web access without proper authentication
if (php_sapi_name() !== 'cli') {
    // Check for cron key in query string or header
    $cronKey = $_GET['cron_key'] ?? $_SERVER['HTTP_X_CRON_KEY'] ?? '';
    $expectedKey = \App\Numz\WHMCS\Settings::get('cron_key', '');

    if (empty($expectedKey) || !hash_equals($expectedKey, $cronKey)) {
        http_response_code(403);
        die('Access Denied - Invalid or missing cron key');
    }

    // Optional: Check IP whitelist
    $ipWhitelist = \App\Numz\WHMCS\Settings::get('cron_ip_whitelist', '');
    if (!empty($ipWhitelist)) {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $allowedIps = array_filter(explode("\n", $ipWhitelist));
        $allowedIps = array_map('trim', $allowedIps);

        if (!in_array($clientIp, $allowedIps)) {
            http_response_code(403);
            die('Access Denied - IP not whitelisted');
        }
    }
}

try {
    echo "WHMCS Cron Starting...\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

    // Run the WHMCS cron command
    \Illuminate\Support\Facades\Artisan::call('whmcs:cron', ['--all' => true]);

    // Output the command result
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "\nCron Completed Successfully\n";

} catch (\Exception $e) {
    echo "Cron Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";

    // Log error
    \Illuminate\Support\Facades\Log::error('WHMCS Cron failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    exit(1);
}

exit(0);
