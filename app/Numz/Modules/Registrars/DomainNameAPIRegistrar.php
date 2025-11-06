<?php

namespace App\Numz\Modules\Registrars;

use App\Numz\Contracts\RegistrarInterface;
use App\Models\ModuleSetting;
use Illuminate\Support\Facades\Http;

class DomainNameAPIRegistrar implements RegistrarInterface
{
    protected $username;
    protected $password;
    protected $baseUrl;
    protected $moduleName = 'domainnameapi';

    public function __construct()
    {
        $this->username = ModuleSetting::get('registrar', $this->moduleName, 'username')
            ?? config('numz.registrars.domainnameapi.username');

        $this->password = ModuleSetting::get('registrar', $this->moduleName, 'password')
            ?? config('numz.registrars.domainnameapi.password');

        $testMode = ModuleSetting::get('registrar', $this->moduleName, 'test_mode', 'false') === 'true';

        $this->baseUrl = $testMode
            ? 'https://api-ote.domainnameapi.com'
            : 'https://api.domainnameapi.com';
    }

    protected function makeRequest(string $endpoint, array $data)
    {
        return Http::withBasicAuth($this->username, $this->password)
            ->post($this->baseUrl . $endpoint, $data)
            ->json();
    }

    public function registerDomain(array $params): array
    {
        try {
            $response = $this->makeRequest('/RegisterDomain', [
                'DomainName' => $params['domain'],
                'Period' => $params['years'] ?? 1,
                'Nameservers' => $params['nameservers'] ?? [],
                'Contacts' => [
                    'Registrant' => [
                        'FirstName' => $params['firstname'],
                        'LastName' => $params['lastname'],
                        'Email' => $params['email'],
                        'Address' => $params['address'],
                        'City' => $params['city'],
                        'State' => $params['state'],
                        'PostalCode' => $params['postcode'],
                        'Country' => $params['country'],
                        'Phone' => $params['phone'],
                    ],
                ],
            ]);

            return [
                'success' => $response['Success'] ?? false,
                'message' => $response['Message'] ?? '',
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function transferDomain(array $params): array
    {
        try {
            $response = $this->makeRequest('/TransferDomain', [
                'DomainName' => $params['domain'],
                'AuthCode' => $params['epp_code'],
                'Period' => $params['years'] ?? 1,
            ]);

            return [
                'success' => $response['Success'] ?? false,
                'message' => $response['Message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function renewDomain(string $domain, int $years): array
    {
        try {
            $response = $this->makeRequest('/RenewDomain', [
                'DomainName' => $domain,
                'Period' => $years,
            ]);

            return [
                'success' => $response['Success'] ?? false,
                'message' => $response['Message'] ?? '',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getNameservers(string $domain): array
    {
        try {
            $response = $this->makeRequest('/GetDomainInfo', [
                'DomainName' => $domain,
            ]);

            return [
                'success' => $response['Success'] ?? false,
                'nameservers' => $response['Nameservers'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function setNameservers(string $domain, array $nameservers): array
    {
        try {
            $response = $this->makeRequest('/ModifyNameservers', [
                'DomainName' => $domain,
                'Nameservers' => $nameservers,
            ]);

            return [
                'success' => $response['Success'] ?? false,
                'message' => $response['Message'] ?? '',
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
            'name' => 'DomainNameAPI',
            'description' => 'Domain registration and management via DomainNameAPI. Supports all major TLDs.',
            'settings' => [
                [
                    'key' => 'username',
                    'label' => 'API Username',
                    'type' => 'text',
                    'encrypted' => false,
                    'required' => true,
                ],
                [
                    'key' => 'password',
                    'label' => 'API Password',
                    'type' => 'password',
                    'encrypted' => true,
                    'required' => true,
                ],
                [
                    'key' => 'test_mode',
                    'label' => 'Test Mode (OTE)',
                    'type' => 'boolean',
                    'encrypted' => false,
                    'required' => false,
                    'default' => 'false',
                ],
            ],
        ];
    }
}
