<?php

namespace App\Numz\Services;

use App\Models\DomainRegistration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DomainRegistrationService
{
    protected string $registrar;
    protected string $apiKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->registrar = config('numz.domain_registrar', 'domainnameapi');
        $this->apiKey = config("numz.registrars.{$this->registrar}.api_key");
        $this->apiUrl = config("numz.registrars.{$this->registrar}.api_url");
    }

    /**
     * Register a new domain
     *
     * @param DomainRegistration $domain
     * @param int $years
     * @return array
     * @throws \Exception
     */
    public function registerDomain(DomainRegistration $domain, int $years = 1): array
    {
        try {
            $response = match ($this->registrar) {
                'domainnameapi' => $this->registerWithDomainNameAPI($domain, $years),
                'namesilo' => $this->registerWithNameSilo($domain, $years),
                'namecheap' => $this->registerWithNamecheap($domain, $years),
                default => throw new \Exception("Unsupported registrar: {$this->registrar}"),
            };

            if ($response['success']) {
                $domain->update([
                    'status' => 'active',
                    'registration_date' => now(),
                    'expiry_date' => now()->addYears($years),
                    'registrar' => $this->registrar,
                ]);

                Log::info("Domain registered successfully", [
                    'domain' => $domain->domain,
                    'registrar' => $this->registrar,
                    'years' => $years,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Domain registration failed", [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Renew an existing domain
     *
     * @param DomainRegistration $domain
     * @param int $years
     * @return array
     * @throws \Exception
     */
    public function renewDomain(DomainRegistration $domain, int $years = 1): array
    {
        try {
            $response = match ($this->registrar) {
                'domainnameapi' => $this->renewWithDomainNameAPI($domain, $years),
                'namesilo' => $this->renewWithNameSilo($domain, $years),
                'namecheap' => $this->renewWithNamecheap($domain, $years),
                default => throw new \Exception("Unsupported registrar: {$this->registrar}"),
            };

            if ($response['success']) {
                $domain->update([
                    'expiry_date' => $domain->expiry_date->addYears($years),
                    'status' => 'active',
                ]);

                Log::info("Domain renewed successfully", [
                    'domain' => $domain->domain,
                    'new_expiry' => $domain->expiry_date->format('Y-m-d'),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Domain renewal failed", [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update nameservers for a domain
     *
     * @param DomainRegistration $domain
     * @param array $nameservers
     * @return array
     * @throws \Exception
     */
    public function updateNameservers(DomainRegistration $domain, array $nameservers): array
    {
        try {
            $response = match ($this->registrar) {
                'domainnameapi' => $this->updateNameserversDomainNameAPI($domain, $nameservers),
                'namesilo' => $this->updateNameserversNameSilo($domain, $nameservers),
                'namecheap' => $this->updateNameserversNamecheap($domain, $nameservers),
                default => throw new \Exception("Unsupported registrar: {$this->registrar}"),
            };

            if ($response['success']) {
                $domain->update([
                    'nameserver1' => $nameservers[0] ?? null,
                    'nameserver2' => $nameservers[1] ?? null,
                    'nameserver3' => $nameservers[2] ?? null,
                    'nameserver4' => $nameservers[3] ?? null,
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("Nameserver update failed", [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get EPP code for domain transfer
     *
     * @param DomainRegistration $domain
     * @return array
     * @throws \Exception
     */
    public function getEppCode(DomainRegistration $domain): array
    {
        try {
            $response = match ($this->registrar) {
                'domainnameapi' => $this->getEppCodeDomainNameAPI($domain),
                'namesilo' => $this->getEppCodeNameSilo($domain),
                'namecheap' => $this->getEppCodeNamecheap($domain),
                default => throw new \Exception("Unsupported registrar: {$this->registrar}"),
            };

            if ($response['success'] && isset($response['epp_code'])) {
                $domain->update(['epp_code' => $response['epp_code']]);
            }

            return $response;
        } catch (\Exception $e) {
            Log::error("EPP code retrieval failed", [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // DomainNameAPI Integration
    protected function registerWithDomainNameAPI(DomainRegistration $domain, int $years): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->post("{$this->apiUrl}/register", [
            'domain' => $domain->domain,
            'years' => $years,
            'nameservers' => array_filter([
                $domain->nameserver1,
                $domain->nameserver2,
                $domain->nameserver3,
                $domain->nameserver4,
            ]),
            'contact' => $this->buildContactInfo($domain->user),
        ]);

        return [
            'success' => $response->successful(),
            'message' => $response->json('message'),
            'data' => $response->json('data'),
        ];
    }

    protected function renewWithDomainNameAPI(DomainRegistration $domain, int $years): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->post("{$this->apiUrl}/renew", [
            'domain' => $domain->domain,
            'years' => $years,
        ]);

        return [
            'success' => $response->successful(),
            'message' => $response->json('message'),
        ];
    }

    protected function updateNameserversDomainNameAPI(DomainRegistration $domain, array $nameservers): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->post("{$this->apiUrl}/nameservers", [
            'domain' => $domain->domain,
            'nameservers' => $nameservers,
        ]);

        return [
            'success' => $response->successful(),
            'message' => $response->json('message'),
        ];
    }

    protected function getEppCodeDomainNameAPI(DomainRegistration $domain): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->get("{$this->apiUrl}/epp-code", [
            'domain' => $domain->domain,
        ]);

        return [
            'success' => $response->successful(),
            'epp_code' => $response->json('epp_code'),
            'message' => $response->json('message'),
        ];
    }

    // NameSilo Integration
    protected function registerWithNameSilo(DomainRegistration $domain, int $years): array
    {
        $response = Http::get("{$this->apiUrl}/registerDomain", [
            'version' => 1,
            'type' => 'xml',
            'key' => $this->apiKey,
            'domain' => $domain->domain,
            'years' => $years,
            'ns1' => $domain->nameserver1 ?? 'ns1.numz.ai',
            'ns2' => $domain->nameserver2 ?? 'ns2.numz.ai',
        ]);

        $xml = simplexml_load_string($response->body());
        $code = (int) $xml->reply->code;

        return [
            'success' => $code === 300,
            'message' => (string) $xml->reply->detail,
            'data' => $xml->reply,
        ];
    }

    protected function renewWithNameSilo(DomainRegistration $domain, int $years): array
    {
        $response = Http::get("{$this->apiUrl}/renewDomain", [
            'version' => 1,
            'type' => 'xml',
            'key' => $this->apiKey,
            'domain' => $domain->domain,
            'years' => $years,
        ]);

        $xml = simplexml_load_string($response->body());
        $code = (int) $xml->reply->code;

        return [
            'success' => $code === 300,
            'message' => (string) $xml->reply->detail,
        ];
    }

    protected function updateNameserversNameSilo(DomainRegistration $domain, array $nameservers): array
    {
        $params = [
            'version' => 1,
            'type' => 'xml',
            'key' => $this->apiKey,
            'domain' => $domain->domain,
        ];

        foreach ($nameservers as $index => $ns) {
            $params['ns' . ($index + 1)] = $ns;
        }

        $response = Http::get("{$this->apiUrl}/changeNameServers", $params);

        $xml = simplexml_load_string($response->body());
        $code = (int) $xml->reply->code;

        return [
            'success' => $code === 300,
            'message' => (string) $xml->reply->detail,
        ];
    }

    protected function getEppCodeNameSilo(DomainRegistration $domain): array
    {
        $response = Http::get("{$this->apiUrl}/retrieveAuthCode", [
            'version' => 1,
            'type' => 'xml',
            'key' => $this->apiKey,
            'domain' => $domain->domain,
        ]);

        $xml = simplexml_load_string($response->body());
        $code = (int) $xml->reply->code;

        return [
            'success' => $code === 300,
            'epp_code' => (string) ($xml->reply->auth_code ?? ''),
            'message' => (string) $xml->reply->detail,
        ];
    }

    // Namecheap Integration
    protected function registerWithNamecheap(DomainRegistration $domain, int $years): array
    {
        // Namecheap API integration would go here
        return [
            'success' => false,
            'message' => 'Namecheap integration not yet implemented',
        ];
    }

    protected function renewWithNamecheap(DomainRegistration $domain, int $years): array
    {
        return [
            'success' => false,
            'message' => 'Namecheap integration not yet implemented',
        ];
    }

    protected function updateNameserversNamecheap(DomainRegistration $domain, array $nameservers): array
    {
        return [
            'success' => false,
            'message' => 'Namecheap integration not yet implemented',
        ];
    }

    protected function getEppCodeNamecheap(DomainRegistration $domain): array
    {
        return [
            'success' => false,
            'message' => 'Namecheap integration not yet implemented',
        ];
    }

    /**
     * Build contact information for domain registration
     *
     * @param \App\Models\User $user
     * @return array
     */
    protected function buildContactInfo($user): array
    {
        return [
            'firstname' => $user->name ?? 'Admin',
            'lastname' => $user->lastname ?? 'User',
            'email' => $user->email,
            'phone' => $user->phone ?? '+1.0000000000',
            'address1' => $user->address ?? 'N/A',
            'city' => $user->city ?? 'N/A',
            'state' => $user->state ?? 'CA',
            'zip' => $user->zip ?? '00000',
            'country' => $user->country ?? 'US',
        ];
    }
}
