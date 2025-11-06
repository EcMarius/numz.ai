<?php

namespace App\Numz\Modules\Provisioning;

use App\Numz\Contracts\ProvisioningInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DirectAdminProvisioning implements ProvisioningInterface
{
    protected string $hostname;
    protected string $username;
    protected string $password;
    protected bool $useSsl;
    protected int $port;

    public function getName(): string
    {
        return 'directadmin';
    }

    public function getDisplayName(): string
    {
        return 'DirectAdmin';
    }

    public function getConfigFields(): array
    {
        return [
            'hostname' => [
                'label' => 'DirectAdmin Hostname',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'Server hostname or IP address',
            ],
            'username' => [
                'label' => 'Admin Username',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'DirectAdmin admin username',
            ],
            'password' => [
                'label' => 'Admin Password',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
                'help' => 'DirectAdmin admin password',
            ],
            'use_ssl' => [
                'label' => 'Use SSL',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
                'default' => true,
            ],
            'port' => [
                'label' => 'DirectAdmin Port',
                'type' => 'number',
                'required' => false,
                'encrypted' => false,
                'default' => 2222,
                'help' => 'Default: 2222',
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->hostname = $settings['hostname'] ?? '';
        $this->username = $settings['username'] ?? '';
        $this->password = $settings['password'] ?? '';
        $this->useSsl = (bool) ($settings['use_ssl'] ?? true);
        $this->port = (int) ($settings['port'] ?? 2222);
    }

    protected function makeRequest(string $command, array $params = []): array
    {
        $protocol = $this->useSsl ? 'https' : 'http';
        $url = "{$protocol}://{$this->hostname}:{$this->port}/CMD_{$command}";

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->asForm()
                ->post($url, $params);

            if ($response->successful()) {
                parse_str($response->body(), $result);
                return ['success' => true, 'data' => $result];
            }

            \Log::error('DirectAdmin API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'API request failed'];
        } catch (\Exception $e) {
            \Log::error('DirectAdmin API exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createAccount(array $params): array
    {
        try {
            $username = $params['username'] ?? Str::lower(Str::random(8));
            $password = $params['password'] ?? Str::random(16);
            $domain = $params['domain'];
            $email = $params['email'];
            $package = $params['package'] ?? 'default';

            $result = $this->makeRequest('ACCOUNT_USER', [
                'action' => 'create',
                'username' => $username,
                'email' => $email,
                'passwd' => $password,
                'passwd2' => $password,
                'domain' => $domain,
                'package' => $package,
                'ip' => 'shared',
                'notify' => 'yes',
            ]);

            if ($result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0') {
                return [
                    'success' => true,
                    'account_id' => $username,
                    'username' => $username,
                    'password' => $password,
                    'message' => 'Account created successfully',
                ];
            }

            return [
                'success' => false,
                'account_id' => null,
                'username' => null,
                'password' => null,
                'message' => $result['data']['details'] ?? 'Account creation failed',
            ];
        } catch (\Exception $e) {
            \Log::error('DirectAdmin createAccount error: ' . $e->getMessage());
            return [
                'success' => false,
                'account_id' => null,
                'username' => null,
                'password' => null,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function suspendAccount(array $params): array
    {
        $result = $this->makeRequest('SELECT_USERS', [
            'suspend' => 'Suspend',
            'select0' => $params['username'],
        ]);

        return [
            'success' => $result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0',
            'message' => $result['success'] ? 'Account suspended' : 'Suspension failed',
        ];
    }

    public function unsuspendAccount(array $params): array
    {
        $result = $this->makeRequest('SELECT_USERS', [
            'unsuspend' => 'Unsuspend',
            'select0' => $params['username'],
        ]);

        return [
            'success' => $result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0',
            'message' => $result['success'] ? 'Account unsuspended' : 'Unsuspend failed',
        ];
    }

    public function terminateAccount(array $params): array
    {
        $result = $this->makeRequest('SELECT_USERS', [
            'delete' => 'yes',
            'select0' => $params['username'],
        ]);

        return [
            'success' => $result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0',
            'message' => $result['success'] ? 'Account terminated' : 'Termination failed',
        ];
    }

    public function changePassword(array $params): array
    {
        $result = $this->makeRequest('CHANGE_PASSWORD', [
            'user' => $params['username'],
            'passwd' => $params['password'],
            'passwd2' => $params['password'],
        ]);

        return [
            'success' => $result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0',
            'message' => $result['success'] ? 'Password changed' : 'Password change failed',
        ];
    }

    public function changePackage(array $params): array
    {
        $result = $this->makeRequest('MODIFY_USER', [
            'user' => $params['username'],
            'package' => $params['package'],
        ]);

        return [
            'success' => $result['success'] && isset($result['data']['error']) && $result['data']['error'] == '0',
            'message' => $result['success'] ? 'Package changed' : 'Package change failed',
        ];
    }

    public function getAccountDetails(string $username): array
    {
        $result = $this->makeRequest('SHOW_USER_CONFIG', [
            'user' => $username,
        ]);

        if ($result['success'] && isset($result['data'])) {
            return [
                'username' => $result['data']['username'] ?? $username,
                'email' => $result['data']['email'] ?? '',
                'package' => $result['data']['package'] ?? '',
                'suspended' => ($result['data']['suspended'] ?? 'no') === 'yes',
            ];
        }

        return [];
    }

    public function testConnection(): array
    {
        $result = $this->makeRequest('API_SHOW_ADMINS');

        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Connection successful',
            ];
        }

        return [
            'success' => false,
            'message' => 'Connection failed',
        ];
    }
}
