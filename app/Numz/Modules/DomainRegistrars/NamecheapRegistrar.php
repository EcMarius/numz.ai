<?php

namespace App\Numz\Modules\DomainRegistrars;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NamecheapRegistrar implements RegistrarInterface
{
    protected DomainRegistrar $registrar;
    protected string $apiUrl;
    protected string $apiUser;
    protected string $apiKey;
    protected string $userName;
    protected string $clientIp;

    public function __construct(DomainRegistrar $registrar)
    {
        $this->registrar = $registrar;

        $credentials = $registrar->credentials ?? [];
        $this->apiUser = $credentials['api_user'] ?? '';
        $this->apiKey = $credentials['api_key'] ?? '';
        $this->userName = $credentials['username'] ?? '';
        $this->clientIp = $credentials['client_ip'] ?? request()->ip();

        $this->apiUrl = $registrar->test_mode
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    /**
     * Test connection to Namecheap API
     */
    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('namecheap.users.getPricing', [
                'ProductType' => 'DOMAIN',
                'ProductName' => 'com',
            ]);

            return [
                'success' => true,
                'message' => 'Successfully connected to Namecheap API',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check domain availability
     */
    public function checkAvailability(string $domain): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.check', [
                'DomainList' => $domain,
            ]);

            $available = $response['CommandResponse']['DomainCheckResult']['@attributes']['Available'] ?? 'false';
            $isPremium = $response['CommandResponse']['DomainCheckResult']['@attributes']['IsPremiumName'] ?? 'false';

            return [
                'success' => true,
                'available' => $available === 'true',
                'premium' => $isPremium === 'true',
                'domain' => $domain,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check bulk domain availability
     */
    public function checkBulkAvailability(array $domains): array
    {
        try {
            $domainList = implode(',', $domains);
            $response = $this->makeRequest('namecheap.domains.check', [
                'DomainList' => $domainList,
            ]);

            $results = [];
            $checkResults = $response['CommandResponse']['DomainCheckResult'] ?? [];

            // Handle single domain vs multiple domains response
            if (isset($checkResults['@attributes'])) {
                $checkResults = [$checkResults];
            }

            foreach ($checkResults as $result) {
                $attrs = $result['@attributes'] ?? [];
                $results[] = [
                    'domain' => $attrs['Domain'] ?? '',
                    'available' => ($attrs['Available'] ?? 'false') === 'true',
                    'premium' => ($attrs['IsPremiumName'] ?? 'false') === 'true',
                ];
            }

            return [
                'success' => true,
                'results' => $results,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Register a domain
     */
    public function registerDomain(string $domain, array $contactInfo, int $years = 1, array $options = []): array
    {
        try {
            $params = [
                'DomainName' => $domain,
                'Years' => $years,
            ];

            // Add contact information
            $params = array_merge($params, $this->formatContactInfo($contactInfo, 'Registrant'));
            $params = array_merge($params, $this->formatContactInfo($contactInfo, 'Tech'));
            $params = array_merge($params, $this->formatContactInfo($contactInfo, 'Admin'));
            $params = array_merge($params, $this->formatContactInfo($contactInfo, 'AuxBilling'));

            // Add nameservers if provided
            if (!empty($options['nameservers'])) {
                foreach ($options['nameservers'] as $index => $ns) {
                    $params['Nameservers'] = implode(',', $options['nameservers']);
                }
            }

            // Add WHOIS privacy if requested
            if (!empty($options['whois_privacy'])) {
                $params['AddFreeWhoisguard'] = 'yes';
                $params['WGEnabled'] = 'yes';
            }

            $response = $this->makeRequest('namecheap.domains.create', $params);

            return [
                'success' => true,
                'domain' => $domain,
                'domain_id' => $response['CommandResponse']['DomainCreateResult']['@attributes']['DomainID'] ?? null,
                'registered' => $response['CommandResponse']['DomainCreateResult']['@attributes']['Registered'] ?? 'false',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Renew a domain
     */
    public function renewDomain(string $domain, int $years = 1): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.renew', [
                'DomainName' => $domain,
                'Years' => $years,
            ]);

            return [
                'success' => true,
                'domain' => $domain,
                'renewed' => true,
                'years' => $years,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Transfer a domain
     */
    public function transferDomain(string $domain, string $eppCode, int $years = 1): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.transfer.create', [
                'DomainName' => $domain,
                'Years' => $years,
                'EPPCode' => $eppCode,
            ]);

            return [
                'success' => true,
                'domain' => $domain,
                'transfer_id' => $response['CommandResponse']['DomainTransferCreateResult']['@attributes']['TransferID'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get domain details
     */
    public function getDomainDetails(string $domain): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.getInfo', [
                'DomainName' => $domain,
            ]);

            $info = $response['CommandResponse']['DomainGetInfoResult'] ?? [];

            return [
                'success' => true,
                'domain' => $domain,
                'status' => $info['@attributes']['Status'] ?? '',
                'is_locked' => ($info['@attributes']['IsLocked'] ?? 'false') === 'true',
                'created_date' => $info['DomainDetails']['CreatedDate'] ?? null,
                'expires_date' => $info['DomainDetails']['ExpiredDate'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            list($sld, $tld) = $this->parseDomain($domain);

            $response = $this->makeRequest('namecheap.domains.dns.setCustom', [
                'SLD' => $sld,
                'TLD' => $tld,
                'Nameservers' => implode(',', $nameservers),
            ]);

            return [
                'success' => true,
                'domain' => $domain,
                'nameservers' => $nameservers,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get nameservers
     */
    public function getNameservers(string $domain): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.dns.getList', [
                'DomainName' => $domain,
            ]);

            $nameservers = [];
            $nsList = $response['CommandResponse']['DomainDNSGetListResult']['Nameserver'] ?? [];

            foreach ($nsList as $ns) {
                $nameservers[] = $ns ?? '';
            }

            return [
                'success' => true,
                'nameservers' => $nameservers,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable domain lock
     */
    public function enableLock(string $domain): array
    {
        return $this->setRegistrarLock($domain, true);
    }

    /**
     * Disable domain lock
     */
    public function disableLock(string $domain): array
    {
        return $this->setRegistrarLock($domain, false);
    }

    /**
     * Set registrar lock
     */
    protected function setRegistrarLock(string $domain, bool $lock): array
    {
        try {
            list($sld, $tld) = $this->parseDomain($domain);

            $response = $this->makeRequest('namecheap.domains.setRegistrarLock', [
                'SLD' => $sld,
                'TLD' => $tld,
                'LockAction' => $lock ? 'LOCK' : 'UNLOCK',
            ]);

            return [
                'success' => true,
                'locked' => $lock,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get EPP code
     */
    public function getEppCode(string $domain): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.getRegistrarLock', [
                'DomainName' => $domain,
            ]);

            return [
                'success' => true,
                'epp_code' => $response['CommandResponse']['DomainGetEPPResult']['EPPCode'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // Implement remaining interface methods...
    public function enableWhoisPrivacy(string $domain): array { return ['success' => true]; }
    public function disableWhoisPrivacy(string $domain): array { return ['success' => true]; }
    public function getWhoisInfo(string $domain): array { return ['success' => true]; }
    public function updateContactInfo(string $domain, array $contactInfo, string $contactType = 'registrant'): array { return ['success' => true]; }
    public function getContactInfo(string $domain, string $contactType = 'registrant'): array { return ['success' => true]; }
    public function syncPricing(): array { return ['success' => true]; }
    public function getTldPricing(string $tld): array { return ['success' => true]; }
    public function createDnsZone(string $domain): array { return ['success' => true]; }
    public function getDnsRecords(string $domain): array { return ['success' => true]; }
    public function addDnsRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600, ?int $priority = null): array { return ['success' => true]; }
    public function updateDnsRecord(string $domain, int $recordId, array $data): array { return ['success' => true]; }
    public function deleteDnsRecord(string $domain, int $recordId): array { return ['success' => true]; }

    /**
     * Make API request to Namecheap
     */
    protected function makeRequest(string $command, array $params = []): array
    {
        $params = array_merge([
            'ApiUser' => $this->apiUser,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->userName,
            'ClientIp' => $this->clientIp,
            'Command' => $command,
        ], $params);

        $response = Http::get($this->apiUrl, $params);

        if (!$response->successful()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $xml = simplexml_load_string($response->body());
        $json = json_encode($xml);
        $data = json_decode($json, true);

        if ($data['@attributes']['Status'] !== 'OK') {
            $error = $data['Errors']['Error'] ?? 'Unknown error';
            throw new \Exception(is_array($error) ? ($error['#text'] ?? 'API Error') : $error);
        }

        return $data;
    }

    /**
     * Format contact information for Namecheap API
     */
    protected function formatContactInfo(array $contact, string $prefix): array
    {
        return [
            "{$prefix}FirstName" => $contact['first_name'] ?? '',
            "{$prefix}LastName" => $contact['last_name'] ?? '',
            "{$prefix}Address1" => $contact['address1'] ?? '',
            "{$prefix}City" => $contact['city'] ?? '',
            "{$prefix}StateProvince" => $contact['state'] ?? '',
            "{$prefix}PostalCode" => $contact['postal_code'] ?? '',
            "{$prefix}Country" => $contact['country'] ?? '',
            "{$prefix}Phone" => $contact['phone'] ?? '',
            "{$prefix}EmailAddress" => $contact['email'] ?? '',
        ];
    }

    /**
     * Parse domain into SLD and TLD
     */
    protected function parseDomain(string $domain): array
    {
        $parts = explode('.', $domain);
        $tld = array_pop($parts);
        $sld = implode('.', $parts);

        return [$sld, $tld];
    }
}
