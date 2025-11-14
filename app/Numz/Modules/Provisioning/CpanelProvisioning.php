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

    protected function makeRequest(string $endpoint, array $params = [], int $retries = 3): array
    {
        $protocol = $this->useSsl ? 'https' : 'http';
        $url = "{$protocol}://{$this->hostname}:{$this->port}/json-api/{$endpoint}";

        $lastException = null;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => "whm {$this->username}:{$this->apiToken}",
                ])
                ->timeout(30)
                ->retry(2, 100) // Retry 2 times with 100ms delay for network issues
                ->get($url, $params);

                if ($response->successful()) {
                    return $response->json();
                }

                // Handle specific error codes
                if ($response->status() === 401) {
                    \Log::error('cPanel API authentication failed', [
                        'endpoint' => $endpoint,
                        'hostname' => $this->hostname,
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Authentication failed. Please check your WHM credentials.',
                        'error_code' => 'AUTH_FAILED',
                    ];
                }

                if ($response->status() === 429) {
                    \Log::warning('cPanel API rate limit reached', [
                        'endpoint' => $endpoint,
                        'attempt' => $attempt,
                    ]);

                    if ($attempt < $retries) {
                        sleep(pow(2, $attempt)); // Exponential backoff
                        continue;
                    }

                    return [
                        'success' => false,
                        'message' => 'API rate limit reached. Please try again later.',
                        'error_code' => 'RATE_LIMIT',
                    ];
                }

                \Log::error('cPanel API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                    'attempt' => $attempt,
                ]);

                if ($attempt < $retries && in_array($response->status(), [500, 502, 503, 504])) {
                    sleep($attempt); // Wait before retry
                    continue;
                }

                return [
                    'success' => false,
                    'message' => "API request failed with status {$response->status()}",
                    'error_code' => 'API_ERROR',
                ];
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastException = $e;
                \Log::error('cPanel API connection failed', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                if ($attempt < $retries) {
                    sleep($attempt);
                    continue;
                }
            } catch (\Exception $e) {
                $lastException = $e;
                \Log::error('cPanel API exception', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'type' => get_class($e),
                ]);
                break;
            }
        }

        return [
            'success' => false,
            'message' => $lastException ? $lastException->getMessage() : 'Unknown error',
            'error_code' => 'EXCEPTION',
        ];
    }

    public function createAccount(array $params): array
    {
        try {
            // Validate required parameters
            $this->validateParams($params, ['domain', 'package', 'email']);

            $username = $params['username'] ?? $this->generateUsername($params['domain']);
            $password = $params['password'] ?? Str::random(16);
            $domain = $this->sanitizeDomain($params['domain']);
            $package = $params['package'];
            $email = filter_var($params['email'], FILTER_VALIDATE_EMAIL);

            if (!$email) {
                return [
                    'success' => false,
                    'message' => 'Invalid email address provided',
                    'error_code' => 'INVALID_EMAIL',
                ];
            }

            // Validate username (cPanel requirements: max 8 chars, alphanumeric)
            $username = substr(preg_replace('/[^a-z0-9]/', '', strtolower($username)), 0, 8);
            if (empty($username)) {
                $username = 'user' . substr(md5($domain), 0, 4);
            }

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
                'message' => $result['metadata']['reason'] ?? $result['message'] ?? 'Account creation failed',
                'error_code' => $result['error_code'] ?? 'CREATE_FAILED',
            ];
        } catch (\Exception $e) {
            \Log::error('cPanel createAccount exception', [
                'error' => $e->getMessage(),
                'domain' => $params['domain'] ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'account_id' => null,
                'username' => null,
                'password' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \InvalidArgumentException("Required parameter '{$field}' is missing");
            }
        }
    }

    protected function generateUsername(string $domain): string
    {
        // Remove TLD and clean domain
        $name = preg_replace('/\.(com|net|org|io|co|uk|us)$/i', '', $domain);
        $name = preg_replace('/[^a-z0-9]/', '', strtolower($name));
        return substr($name, 0, 8);
    }

    protected function sanitizeDomain(string $domain): string
    {
        // Remove protocol, www, and trailing slashes
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');
        return strtolower($domain);
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
