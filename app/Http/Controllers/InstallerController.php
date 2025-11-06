<?php

namespace App\Http\Controllers;

use App\Models\SystemInstallation;
use App\Models\User;
use App\Numz\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstallerController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Show installer welcome page
     */
    public function index()
    {
        if (SystemInstallation::isInstalled()) {
            return redirect('/')->with('error', 'NUMZ.AI is already installed');
        }

        return view('installer.welcome');
    }

    /**
     * Check system requirements
     */
    public function requirements()
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version >= 8.2',
                'check' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'check' => extension_loaded('pdo'),
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'check' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'name' => 'OpenSSL Extension',
                'check' => extension_loaded('openssl'),
            ],
            'tokenizer' => [
                'name' => 'Tokenizer Extension',
                'check' => extension_loaded('tokenizer'),
            ],
            'json' => [
                'name' => 'JSON Extension',
                'check' => extension_loaded('json'),
            ],
            'curl' => [
                'name' => 'cURL Extension',
                'check' => extension_loaded('curl'),
            ],
        ];

        $permissions = [
            'storage' => [
                'name' => 'storage/',
                'check' => is_writable(storage_path()),
            ],
            'bootstrap_cache' => [
                'name' => 'bootstrap/cache/',
                'check' => is_writable(base_path('bootstrap/cache')),
            ],
        ];

        $allPassed = !in_array(false, array_column($requirements, 'check'))
                  && !in_array(false, array_column($permissions, 'check'));

        return view('installer.requirements', compact('requirements', 'permissions', 'allPassed'));
    }

    /**
     * Show license verification form
     */
    public function license()
    {
        return view('installer.license');
    }

    /**
     * Verify license
     */
    public function verifyLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|min:20',
            'email' => 'required|email',
        ]);

        $result = $this->licenseService->verify($request->license_key, $request->email);

        if ($result['valid']) {
            session(['license_verified' => true, 'license_data' => $request->only(['license_key', 'email'])]);
            return response()->json(['success' => true]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }

    /**
     * Show database configuration form
     */
    public function database()
    {
        if (!session('license_verified')) {
            return redirect()->route('installer.license');
        }

        return view('installer.database');
    }

    /**
     * Test database connection
     */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_name' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        try {
            $connection = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_name}",
                $request->db_username,
                $request->db_password
            );

            session(['database_config' => $request->all()]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show admin account creation form
     */
    public function admin()
    {
        if (!session('database_config')) {
            return redirect()->route('installer.database');
        }

        return view('installer.admin');
    }

    /**
     * Complete installation
     */
    public function install(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            DB::beginTransaction();

            // Update .env file with database config
            $dbConfig = session('database_config');
            $this->updateEnvFile([
                'DB_HOST' => $dbConfig['db_host'],
                'DB_PORT' => $dbConfig['db_port'],
                'DB_DATABASE' => $dbConfig['db_name'],
                'DB_USERNAME' => $dbConfig['db_username'],
                'DB_PASSWORD' => $dbConfig['db_password'],
            ]);

            // Clear config cache
            Artisan::call('config:clear');

            // Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // Create admin user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            // Assign admin role if using Spatie permissions
            if (class_exists('\Spatie\Permission\Models\Role')) {
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                $user->assignRole($adminRole);
            }

            // Create installation record
            $licenseData = session('license_data');
            SystemInstallation::create([
                'is_installed' => true,
                'license_key' => $licenseData['license_key'],
                'license_email' => $licenseData['email'],
                'license_status' => 'active',
                'license_verified_at' => now(),
                'installed_at' => now(),
                'installation_id' => $this->licenseService->generateInstallationId(),
                'app_version' => '1.0.0',
            ]);

            DB::commit();

            // Clear all sessions except auth
            session()->forget(['license_verified', 'license_data', 'database_config']);

            // Log in the user
            auth()->login($user);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update .env file
     */
    protected function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}
