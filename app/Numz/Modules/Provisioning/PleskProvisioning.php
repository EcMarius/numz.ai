<?php

namespace App\Numz\Modules\Provisioning;

use App\Numz\Contracts\ProvisioningInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PleskProvisioning implements ProvisioningInterface
{
    protected string $hostname;
    protected string $apiKey;
    protected bool $useSsl;
    protected int $port;

    public function getName(): string
    {
        return 'plesk';
    }

    public function getDisplayName(): string
    {
        return 'Plesk';
    }

    public function getConfigFields(): array
    {
        return [
            'hostname' => [
                'label' => 'Plesk Hostname',
                'type' => 'text',
                'required' => true,
                'encrypted' => false,
                'help' => 'Server hostname or IP address',
            ],
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'encrypted' => true,
                'help' => 'Plesk API key for authentication',
            ],
            'use_ssl' => [
                'label' => 'Use SSL',
                'type' => 'checkbox',
                'required' => false,
                'encrypted' => false,
                'default' => true,
            ],
            'port' => [
                'label' => 'Plesk Port',
                'type' => 'number',
                'required' => false,
                'encrypted' => false,
                'default' => 8443,
                'help' => 'Default: 8443',
            ],
        ];
    }

    public function initialize(array $settings): void
    {
        $this->hostname = $settings['hostname'] ?? '';
        $this->apiKey = $settings['api_key'] ?? '';
        $this->useSsl = (bool) ($settings['use_ssl'] ?? true);
        $this->port = (int) ($settings['port'] ?? 8443);
    }

    protected function makeRequest(string $xml): array
    {
        $protocol = $this->useSsl ? 'https' : 'http';
        $url = "{$protocol}://{$this->hostname}:{$this->port}/enterprise/control/agent.php";

        try {
            $response = Http::withHeaders([
                'HTTP_AUTH_LOGIN' => $this->apiKey,
                'HTTP_AUTH_PASSWD' => '',
                'Content-Type' => 'text/xml',
            ])
            ->timeout(30)
            ->send('POST', $url, [
                'body' => $xml,
            ]);

            if ($response->successful()) {
                $xmlResponse = simplexml_load_string($response->body());
                return ['success' => true, 'data' => $xmlResponse];
            }

            \Log::error('Plesk API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'API request failed'];
        } catch (\Exception $e) {
            \Log::error('Plesk API exception: ' . $e->getMessage());
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

            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <customer>
        <add>
            <gen_info>
                <cname>{$username}</cname>
                <pname>{$username}</pname>
                <login>{$username}</login>
                <passwd>{$password}</passwd>
                <email>{$email}</email>
            </gen_info>
        </add>
    </customer>
    <webspace>
        <add>
            <gen_setup>
                <name>{$domain}</name>
                <owner-login>{$username}</owner-login>
                <htype>vrt_hst</htype>
            </gen_setup>
            <hosting>
                <vrt_hst>
                    <property>
                        <name>ftp_login</name>
                        <value>{$username}</value>
                    </property>
                    <property>
                        <name>ftp_password</name>
                        <value>{$password}</value>
                    </property>
                    <ip_address/>
                </vrt_hst>
            </hosting>
        </add>
    </webspace>
</packet>";

            $result = $this->makeRequest($xml);

            if ($result['success']) {
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
                'message' => $result['message'] ?? 'Account creation failed',
            ];
        } catch (\Exception $e) {
            \Log::error('Plesk createAccount error: ' . $e->getMessage());
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
        $username = $params['username'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <webspace>
        <set>
            <filter>
                <name>{$username}</name>
            </filter>
            <values>
                <gen_setup>
                    <status>16</status>
                </gen_setup>
            </values>
        </set>
    </webspace>
</packet>";

        $result = $this->makeRequest($xml);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Account suspended' : 'Suspension failed',
        ];
    }

    public function unsuspendAccount(array $params): array
    {
        $username = $params['username'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <webspace>
        <set>
            <filter>
                <name>{$username}</name>
            </filter>
            <values>
                <gen_setup>
                    <status>0</status>
                </gen_setup>
            </values>
        </set>
    </webspace>
</packet>";

        $result = $this->makeRequest($xml);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Account unsuspended' : 'Unsuspend failed',
        ];
    }

    public function terminateAccount(array $params): array
    {
        $username = $params['username'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <webspace>
        <del>
            <filter>
                <name>{$username}</name>
            </filter>
        </del>
    </webspace>
</packet>";

        $result = $this->makeRequest($xml);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Account terminated' : 'Termination failed',
        ];
    }

    public function changePassword(array $params): array
    {
        $username = $params['username'];
        $password = $params['password'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <customer>
        <set>
            <filter>
                <login>{$username}</login>
            </filter>
            <values>
                <gen_info>
                    <passwd>{$password}</passwd>
                </gen_info>
            </values>
        </set>
    </customer>
</packet>";

        $result = $this->makeRequest($xml);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Password changed' : 'Password change failed',
        ];
    }

    public function changePackage(array $params): array
    {
        $username = $params['username'];
        $package = $params['package'];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <webspace>
        <set>
            <filter>
                <name>{$username}</name>
            </filter>
            <values>
                <plan-name>{$package}</plan-name>
            </values>
        </set>
    </webspace>
</packet>";

        $result = $this->makeRequest($xml);

        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'Package changed' : 'Package change failed',
        ];
    }

    public function getAccountDetails(string $username): array
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <webspace>
        <get>
            <filter>
                <name>{$username}</name>
            </filter>
            <dataset>
                <gen_info/>
                <stat/>
            </dataset>
        </get>
    </webspace>
</packet>";

        $result = $this->makeRequest($xml);

        if ($result['success'] && isset($result['data']->webspace->get->result)) {
            $data = $result['data']->webspace->get->result;
            return [
                'username' => (string) $data->data->gen_info->name,
                'status' => (string) $data->data->gen_info->status,
                'disk_used' => (string) $data->data->stat->disk_space,
            ];
        }

        return [];
    }

    public function testConnection(): array
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<packet>
    <server>
        <get>
            <stat/>
        </get>
    </server>
</packet>";

        $result = $this->makeRequest($xml);

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
