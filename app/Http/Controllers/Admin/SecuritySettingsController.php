<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SecuritySettingsController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->middleware(['auth', 'role:super-admin']);
        $this->activityLogger = $activityLogger;
    }

    /**
     * Show security settings
     */
    public function index()
    {
        $settings = [
            'password' => [
                'min_length' => config('security.password.min_length', 8),
                'require_uppercase' => config('security.password.require_uppercase', true),
                'require_lowercase' => config('security.password.require_lowercase', true),
                'require_number' => config('security.password.require_number', true),
                'require_special' => config('security.password.require_special', true),
            ],
            'session' => [
                'timeout' => config('security.session.timeout', 1800),
            ],
            'brute_force' => [
                'max_attempts' => config('security.brute_force.max_attempts', 5),
                'decay_minutes' => config('security.brute_force.decay_minutes', 15),
            ],
            'headers' => [
                'hsts_enabled' => config('security.headers.hsts.enabled', true),
                'csp_enabled' => config('security.headers.csp.enabled', false),
            ],
            '2fa' => [
                'enforce' => config('auth.2fa.enforce', false),
                'enforce_for_roles' => config('auth.2fa.enforce_for_roles', []),
            ],
        ];

        return view('admin.security.settings', compact('settings'));
    }

    /**
     * Update security settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'password_min_length' => 'required|integer|min:6|max:64',
            'password_require_uppercase' => 'boolean',
            'password_require_lowercase' => 'boolean',
            'password_require_number' => 'boolean',
            'password_require_special' => 'boolean',
            'session_timeout' => 'required|integer|min:300|max:86400',
            'brute_force_max_attempts' => 'required|integer|min:3|max:20',
            'brute_force_decay_minutes' => 'required|integer|min:5|max:120',
        ]);

        // Update config (in production, this should update a database or config file)
        Config::set('security.password.min_length', $request->password_min_length);
        Config::set('security.password.require_uppercase', $request->boolean('password_require_uppercase'));
        Config::set('security.password.require_lowercase', $request->boolean('password_require_lowercase'));
        Config::set('security.password.require_number', $request->boolean('password_require_number'));
        Config::set('security.password.require_special', $request->boolean('password_require_special'));
        Config::set('security.session.timeout', $request->session_timeout);
        Config::set('security.brute_force.max_attempts', $request->brute_force_max_attempts);
        Config::set('security.brute_force.decay_minutes', $request->brute_force_decay_minutes);

        // Log changes
        $this->activityLogger->logSettingsChange(
            'security_settings',
            null,
            $request->all()
        );

        return back()->with('success', 'Security settings updated successfully.');
    }

    /**
     * Show IP whitelist
     */
    public function ipWhitelist()
    {
        $whitelist = config('security.ip_whitelist', []);

        return view('admin.security.ip-whitelist', compact('whitelist'));
    }

    /**
     * Update IP whitelist
     */
    public function updateIpWhitelist(Request $request)
    {
        $request->validate([
            'ips' => 'required|string',
        ]);

        $ips = array_filter(array_map('trim', explode("\n", $request->ips)));

        // Validate IP addresses
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP) && !str_contains($ip, '/') && !str_contains($ip, '*')) {
                return back()->withErrors(['ips' => "Invalid IP address: {$ip}"]);
            }
        }

        // Update config
        Config::set('security.ip_whitelist', $ips);

        $this->activityLogger->logSettingsChange(
            'ip_whitelist',
            config('security.ip_whitelist', []),
            $ips
        );

        return back()->with('success', 'IP whitelist updated successfully.');
    }
}
