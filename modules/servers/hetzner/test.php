<?php
/**
 * Hetzner Module Test Script
 *
 * This script tests the Hetzner module installation and API connectivity
 * Run from command line: php test.php
 * Or access via browser (set credentials below)
 */

// Prevent direct browser access in production
// Remove or comment out for browser testing
if (php_sapi_name() !== 'cli') {
    // Uncomment to allow browser access:
    // die('This script should be run from command line. To run in browser, comment out this check.');
}

echo "=================================================\n";
echo "Hetzner Module Test Script\n";
echo "=================================================\n\n";

// Configuration - Replace with your actual credentials
$CLOUD_API_TOKEN = 'YOUR_CLOUD_API_TOKEN_HERE';
$ROBOT_USERNAME = 'YOUR_ROBOT_USERNAME_HERE';
$ROBOT_PASSWORD = 'YOUR_ROBOT_PASSWORD_HERE';

// Test results
$tests = [];
$passed = 0;
$failed = 0;

/**
 * Test helper function
 */
function test($name, $callback) {
    global $tests, $passed, $failed;

    echo "Testing: {$name}... ";

    try {
        $result = $callback();

        if ($result === true) {
            echo "✓ PASS\n";
            $tests[$name] = 'PASS';
            $passed++;
            return true;
        } else {
            echo "✗ FAIL: {$result}\n";
            $tests[$name] = "FAIL: {$result}";
            $failed++;
            return false;
        }
    } catch (Exception $e) {
        echo "✗ ERROR: {$e->getMessage()}\n";
        $tests[$name] = "ERROR: {$e->getMessage()}";
        $failed++;
        return false;
    }
}

// ============================================================================
// PHP REQUIREMENTS TESTS
// ============================================================================

echo "\n--- PHP Requirements ---\n\n";

test('PHP Version >= 7.4', function() {
    $version = phpversion();
    if (version_compare($version, '7.4.0', '>=')) {
        echo "({$version}) ";
        return true;
    }
    return "PHP {$version} is too old, requires 7.4+";
});

test('cURL Extension', function() {
    if (extension_loaded('curl')) {
        $version = curl_version();
        echo "({$version['version']}) ";
        return true;
    }
    return "cURL extension not loaded";
});

test('JSON Extension', function() {
    if (extension_loaded('json')) {
        return true;
    }
    return "JSON extension not loaded";
});

test('OpenSSL Extension', function() {
    if (extension_loaded('openssl')) {
        return true;
    }
    return "OpenSSL extension not loaded";
});

// ============================================================================
// FILE STRUCTURE TESTS
// ============================================================================

echo "\n--- File Structure ---\n\n";

$moduleDir = __DIR__;

test('Main Module File', function() use ($moduleDir) {
    $file = $moduleDir . '/hetzner.php';
    if (file_exists($file) && is_readable($file)) {
        echo "(" . number_format(filesize($file)) . " bytes) ";
        return true;
    }
    return "File not found or not readable: {$file}";
});

test('Hooks File', function() use ($moduleDir) {
    $file = $moduleDir . '/hooks.php';
    if (file_exists($file) && is_readable($file)) {
        echo "(" . number_format(filesize($file)) . " bytes) ";
        return true;
    }
    return "File not found or not readable: {$file}";
});

test('Client Area Template', function() use ($moduleDir) {
    $file = $moduleDir . '/templates/clientarea.tpl';
    if (file_exists($file) && is_readable($file)) {
        echo "(" . number_format(filesize($file)) . " bytes) ";
        return true;
    }
    return "File not found or not readable: {$file}";
});

test('README Documentation', function() use ($moduleDir) {
    $file = $moduleDir . '/README.md';
    if (file_exists($file) && is_readable($file)) {
        return true;
    }
    return "File not found: {$file}";
});

test('Installation Guide', function() use ($moduleDir) {
    $file = $moduleDir . '/INSTALL.md';
    if (file_exists($file) && is_readable($file)) {
        return true;
    }
    return "File not found: {$file}";
});

// ============================================================================
// MODULE SYNTAX TESTS
// ============================================================================

echo "\n--- Module Syntax ---\n\n";

test('PHP Syntax Check', function() use ($moduleDir) {
    $file = $moduleDir . '/hetzner.php';
    $output = [];
    $returnCode = 0;

    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);

    if ($returnCode === 0) {
        return true;
    }
    return "Syntax error: " . implode("\n", $output);
});

test('Module Functions Defined', function() use ($moduleDir) {
    require_once $moduleDir . '/hetzner.php';

    $requiredFunctions = [
        'hetzner_MetaData',
        'hetzner_ConfigOptions',
        'hetzner_CreateAccount',
        'hetzner_SuspendAccount',
        'hetzner_UnsuspendAccount',
        'hetzner_TerminateAccount',
        'hetzner_TestConnection',
    ];

    foreach ($requiredFunctions as $func) {
        if (!function_exists($func)) {
            return "Required function not found: {$func}";
        }
    }

    echo "(" . count($requiredFunctions) . " functions) ";
    return true;
});

test('HetznerAPI Class', function() use ($moduleDir) {
    require_once $moduleDir . '/hetzner.php';

    if (!class_exists('HetznerAPI')) {
        return "HetznerAPI class not found";
    }

    $api = new HetznerAPI('test', 'test', 'test');

    if (!method_exists($api, 'cloudRequest')) {
        return "cloudRequest method not found";
    }

    if (!method_exists($api, 'robotRequest')) {
        return "robotRequest method not found";
    }

    return true;
});

// ============================================================================
// API CONNECTIVITY TESTS
// ============================================================================

echo "\n--- API Connectivity ---\n\n";

if ($CLOUD_API_TOKEN === 'YOUR_CLOUD_API_TOKEN_HERE') {
    echo "⚠ Warning: Cloud API token not configured. Skipping Cloud API tests.\n\n";
    test('Cloud API Token Configured', function() {
        return "Token not configured in test script";
    });
} else {
    test('Cloud API Authentication', function() use ($CLOUD_API_TOKEN) {
        require_once __DIR__ . '/hetzner.php';

        $api = new HetznerAPI($CLOUD_API_TOKEN, '', '');
        $result = $api->listLocations();

        if (isset($result['error'])) {
            return "API Error: " . $result['error'];
        }

        if (!isset($result['locations'])) {
            return "Invalid API response";
        }

        echo "(" . count($result['locations']) . " locations) ";
        return true;
    });

    test('Cloud API - List Server Types', function() use ($CLOUD_API_TOKEN) {
        require_once __DIR__ . '/hetzner.php';

        $api = new HetznerAPI($CLOUD_API_TOKEN, '', '');
        $result = $api->listServerTypes();

        if (isset($result['error'])) {
            return "API Error: " . $result['error'];
        }

        if (!isset($result['server_types'])) {
            return "Invalid API response";
        }

        echo "(" . count($result['server_types']) . " types) ";
        return true;
    });

    test('Cloud API - List Images', function() use ($CLOUD_API_TOKEN) {
        require_once __DIR__ . '/hetzner.php';

        $api = new HetznerAPI($CLOUD_API_TOKEN, '', '');
        $result = $api->listImages();

        if (isset($result['error'])) {
            return "API Error: " . $result['error'];
        }

        if (!isset($result['images'])) {
            return "Invalid API response";
        }

        $systemImages = array_filter($result['images'], function($img) {
            return $img['type'] === 'system';
        });

        echo "(" . count($systemImages) . " images) ";
        return true;
    });
}

if ($ROBOT_USERNAME === 'YOUR_ROBOT_USERNAME_HERE') {
    echo "\n⚠ Warning: Robot credentials not configured. Skipping Robot API tests.\n\n";
    test('Robot API Credentials Configured', function() {
        return "Credentials not configured in test script";
    });
} else {
    test('Robot API Authentication', function() use ($ROBOT_USERNAME, $ROBOT_PASSWORD) {
        require_once __DIR__ . '/hetzner.php';

        $api = new HetznerAPI('', $ROBOT_USERNAME, $ROBOT_PASSWORD);
        $result = $api->listDedicatedServers();

        if (isset($result['error'])) {
            return "API Error: " . $result['error'];
        }

        // Robot API returns array of servers or empty array
        if (!is_array($result)) {
            return "Invalid API response";
        }

        echo "(" . count($result) . " servers) ";
        return true;
    });
}

// ============================================================================
// FUNCTION TESTS
// ============================================================================

echo "\n--- Module Functions ---\n\n";

test('MetaData Function', function() use ($moduleDir) {
    require_once $moduleDir . '/hetzner.php';

    $metadata = hetzner_MetaData();

    if (!is_array($metadata)) {
        return "MetaData did not return an array";
    }

    $required = ['DisplayName', 'APIVersion'];
    foreach ($required as $key) {
        if (!isset($metadata[$key])) {
            return "Missing required key: {$key}";
        }
    }

    echo "({$metadata['DisplayName']} v{$metadata['APIVersion']}) ";
    return true;
});

test('ConfigOptions Function', function() use ($moduleDir) {
    require_once $moduleDir . '/hetzner.php';

    $options = hetzner_ConfigOptions();

    if (!is_array($options)) {
        return "ConfigOptions did not return an array";
    }

    $required = ['service_type', 'server_type', 'location', 'image'];
    foreach ($required as $key) {
        if (!isset($options[$key])) {
            return "Missing required option: {$key}";
        }
    }

    echo "(" . count($options) . " options) ";
    return true;
});

test('TestConnection Function', function() use ($moduleDir, $CLOUD_API_TOKEN) {
    if ($CLOUD_API_TOKEN === 'YOUR_CLOUD_API_TOKEN_HERE') {
        return "API token not configured";
    }

    require_once $moduleDir . '/hetzner.php';

    $params = [
        'serverapitoken' => $CLOUD_API_TOKEN,
    ];

    $result = hetzner_TestConnection($params);

    if (!isset($result['success']) || $result['success'] !== true) {
        $error = $result['error'] ?? 'Unknown error';
        return "Connection test failed: {$error}";
    }

    return true;
});

// ============================================================================
// SUMMARY
// ============================================================================

echo "\n=================================================\n";
echo "Test Summary\n";
echo "=================================================\n\n";

echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed} ✓\n";
echo "Failed: {$failed} ✗\n";
echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n\n";

if ($failed > 0) {
    echo "⚠ Some tests failed. Please review the errors above.\n\n";

    echo "Failed Tests:\n";
    foreach ($tests as $name => $result) {
        if (strpos($result, 'FAIL') === 0 || strpos($result, 'ERROR') === 0) {
            echo "  - {$name}: {$result}\n";
        }
    }
    echo "\n";

    exit(1);
} else {
    echo "✓ All tests passed! Module is ready to use.\n\n";

    echo "Next Steps:\n";
    echo "1. Configure a product in WHMCS\n";
    echo "2. Set your API credentials\n";
    echo "3. Create a test order\n";
    echo "4. Verify server provisioning\n\n";

    exit(0);
}
