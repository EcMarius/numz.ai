<?php

namespace App\Numz\Modules\Provisioning;

use App\Numz\Contracts\ProvisioningInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CpanelProvisioning implements ProvisioningInterface
{
    protected string $hostname;
    protected string $username;
    protected string $apiToken;
    protected bool $useSsl;
    protected int $port;

    public function getName(): string
    {
        return 'cpanel';
    }

    public function getDisplayName(): string
    {
        return 'cPanel/WHM';
    }

    public function getConfigFields(): array
    {
        return [
            'hostname' => [
                'label' => 'WHM Hostname',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'Server hostname or IP address',
            ],
            'username' => [
                'label' => 'WHM Username',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'WHM root username',
            ],
            'api_token' => [
                'label' => 'WHM API Token',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
                'help' => 'WHM API token for authentication',
            ],
            'use_ssl' => [
                'label' => 'Use SSL',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
                'default' => true,
            ],
            'port' => [
                'label' => 'WHM Port',
                'type' => 'number',
                'required' => false,
                'encrypted' => false,
                'default' => 2087,
                'help' => 'Default: 2087 for SSL, 2086 for non-SSL',
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->hostname = $settings['hostname'] ?? '';
        $this->username = $settings['username'] ?? '';
        $this->apiToken = $settings['api_token'] ?? '';
        $this->useSsl = (bool) ($settings['use_ssl'] ?? true);
        $this->port = (int) ($settings['port'] ?? 2087);
    }

    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $protocol = $this->useSsl ? 'https' : 'http';
        $url = "{$protocol}://{$this->hostname}:{$this->port}/json-api/{$endpoint}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "whm {$this->username}:{$this->apiToken}",
            ])
            ->timeout(30)
            ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('cPanel API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'API request failed'];
        } catch (\Exception $e) {
            \Log::error('cPanel API exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function createAccount(array $params): array
    {
        try {
            $username = $params['username'] ?? Str::lower(Str::random(8));
            $password = $params['password'] ?? Str::random(16);
            $domain = $params['domain'];
            $package = $params['package'];
            $email = $params['email'];

            $result = $this->makeRequest('createacct', [
                'username' => $username,
                'domain' => $domain,
                'plan' => $package,
                'contactemail' => $email,
                'password' => $password,
            ]);

            if (isset($result['metadata']['result']) && $result['metadata']['result'] == 1) {
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
                'message' => $result['metadata']['reason'] ?? 'Account creation failed',
            ];
        } catch (\Exception $e) {
            \Log::error('cPanel createAccount error: ' . $e->getMessage());
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
        $result = $this->makeRequest('suspendacct', [
            'user' => $params['username'],
            'reason' => $params['reason'] ?? 'Suspended by billing system',
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Suspension completed',
        ];
    }

    public function unsuspendAccount(array $params): array
    {
        $result = $this->makeRequest('unsuspendacct', [
            'user' => $params['username'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Account unsuspended',
        ];
    }

    public function terminateAccount(array $params): array
    {
        $result = $this->makeRequest('removeacct', [
            'user' => $params['username'],
            'keepdns' => 0,
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Account terminated',
        ];
    }

    public function changePassword(array $params): array
    {
        $result = $this->makeRequest('passwd', [
            'user' => $params['username'],
            'password' => $params['password'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Password changed',
        ];
    }

    public function changePackage(array $params): array
    {
        $result = $this->makeRequest('changepackage', [
            'user' => $params['username'],
            'pkg' => $params['package'],
        ]);

        return [
            'success' => isset($result['metadata']['result']) && $result['metadata']['result'] == 1,
            'message' => $result['metadata']['reason'] ?? 'Package changed',
        ];
    }

    public function getAccountDetails(string $username): array
    {
        $result = $this->makeRequest('accountsummary', [
            'user' => $username,
        ]);

        if (isset($result['data']['acct'][0])) {
            $account = $result['data']['acct'][0];
            return [
                'username' => $account['user'],
                'domain' => $account['domain'],
                'email' => $account['email'],
                'disk_used' => $account['diskused'],
                'disk_limit' => $account['disklimit'],
                'suspended' => $account['suspended'] == 1,
                'package' => $account['plan'],
            ];
        }

        return [];
    }

    public function testConnection(): array
    {
        $result = $this->makeRequest('version');

        if (isset($result['version'])) {
            return [
                'success' => true,
                'message' => 'Connection successful. WHM version: ' . $result['version'],
            ];
        }

        return [
            'success' => false,
            'message' => 'Connection failed',
        ];
    }
}
