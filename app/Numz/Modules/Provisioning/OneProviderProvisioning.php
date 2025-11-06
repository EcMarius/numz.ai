<?php

namespace App\Numz\Modules\Provisioning;

use App\Numz\Contracts\ProvisioningInterface;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Http;

class OneProviderProvisioning implements ProvisioningInterface
{
    protected $apiKey;
    protected $baseUrl = 'https://api.oneprovider.com/v1';
    protected $moduleName = 'oneprovider';

    public function __construct()
    {
        $this->apiKey = ModuleSetting::get('provisioning', $this->moduleName, 'api_key')
            ?? config('numz.provisioning.oneprovider.api_key');
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        return Http::withToken($this->apiKey)
            ->$method($this->baseUrl . $endpoint, $data)
            ->json();
    }

    public function createAccount(array $params): array
    {
        try {
            $response = $this->makeRequest('post', '/servers', [
                'package_id' => $params['package_id'],
                'hostname' => $params['domain'],
                'os' => $params['os'] ?? 'ubuntu20',
                'location' => $params['location'] ?? 'us-east',
                'ram' => $params['ram'] ?? 2,
                'storage' => $params['storage'] ?? 50,
            ]);

            return [
                'success' => $response['status'] === 'success',
                'server_id' => $response['server']['id'] ?? null,
                'ip_address' => $response['server']['ip_address'] ?? null,
                'username' => $response['server']['username'] ?? null,
                'password' => $response['server']['password'] ?? null,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function suspendAccount(array $params): array
    {
        try {
            $response = $this->makeRequest('post', "/servers/{$params['server_id']}/suspend", []);

            return [
                'success' => $response['status'] === 'success',
                'message' => $response['message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function unsuspendAccount(array $params): array
    {
        try {
            $response = $this->makeRequest('post', "/servers/{$params['server_id']}/unsuspend", []);

            return [
                'success' => $response['status'] === 'success',
                'message' => $response['message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function terminateAccount(array $params): array
    {
        try {
            $response = $this->makeRequest('delete', "/servers/{$params['server_id']}", []);

            return [
                'success' => $response['status'] === 'success',
                'message' => $response['message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function changePassword(array $params): array
    {
        try {
            $response = $this->makeRequest('post', "/servers/{$params['server_id']}/password", [
                'password' => $params['password'],
            ]);

            return [
                'success' => $response['status'] === 'success',
                'message' => $response['message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getConfig(): array
    {
        return [
            'name' => 'OneProvider',
            'description' => 'VPS/Cloud server provisioning via OneProvider. Automatic server deployment and management.',
            'settings' => [
                [
                    'key' => 'api_key',
                    'label' => 'API Key',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => true,
                ],
                [
                    'key' => 'default_location',
                    'label' => 'Default Location',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'us-east',
                ],
            ],
        ];
    }
}
