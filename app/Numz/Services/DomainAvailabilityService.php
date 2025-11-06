<?php

namespace App\Numz\Services;

use App\Models\DomainRegistrar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DomainAvailabilityService
{
    /**
     * Check if a domain is available
     */
    public function checkAvailability(string $domain, ?int $registrarId = null): array
    {
        // Normalize domain
        $domain = strtolower(trim($domain));

        // Check cache first
        $cacheKey = "domain_availability:{$domain}";
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return [
                'success' => true,
                'domain' => $domain,
                'available' => $cached['available'],
                'premium' => $cached['premium'] ?? false,
                'premium_price' => $cached['premium_price'] ?? null,
                'cached' => true,
            ];
        }

        // Get registrar
        if ($registrarId) {
            $registrar = DomainRegistrar::find($registrarId);
        } else {
            $registrar = DomainRegistrar::where('is_enabled', true)
                ->where('is_available', true)
                ->first();
        }

        if (!$registrar) {
            return [
                'success' => false,
                'error' => 'No enabled registrar found',
            ];
        }

        try {
            // Check availability through registrar
            $module = $registrar->getModuleInstance();
            $result = $module->checkAvailability($domain);

            if ($result['success']) {
                // Cache result for 1 hour
                Cache::put($cacheKey, [
                    'available' => $result['available'],
                    'premium' => $result['premium'] ?? false,
                    'premium_price' => $result['premium_price'] ?? null,
                ], 3600);

                // Store in database cache
                DB::table('domain_availability_cache')->updateOrInsert(
                    ['domain_name' => $domain],
                    [
                        'tld' => $this->getTld($domain),
                        'is_available' => $result['available'],
                        'is_premium' => $result['premium'] ?? false,
                        'premium_price' => $result['premium_price'] ?? null,
                        'checked_at' => now(),
                        'expires_at' => now()->addHour(),
                        'updated_at' => now(),
                    ]
                );
            }

            return array_merge($result, ['cached' => false]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check availability for multiple domains
     */
    public function checkBulkAvailability(array $domains, ?int $registrarId = null): array
    {
        $results = [];

        // Normalize domains
        $domains = array_map(fn($d) => strtolower(trim($d)), $domains);

        // Check cache first
        $uncachedDomains = [];
        foreach ($domains as $domain) {
            $cacheKey = "domain_availability:{$domain}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $results[] = [
                    'domain' => $domain,
                    'available' => $cached['available'],
                    'premium' => $cached['premium'] ?? false,
                    'premium_price' => $cached['premium_price'] ?? null,
                    'cached' => true,
                ];
            } else {
                $uncachedDomains[] = $domain;
            }
        }

        // If all domains are cached, return
        if (empty($uncachedDomains)) {
            return [
                'success' => true,
                'results' => $results,
            ];
        }

        // Get registrar
        if ($registrarId) {
            $registrar = DomainRegistrar::find($registrarId);
        } else {
            $registrar = DomainRegistrar::where('is_enabled', true)
                ->where('is_available', true)
                ->first();
        }

        if (!$registrar) {
            return [
                'success' => false,
                'error' => 'No enabled registrar found',
            ];
        }

        try {
            // Check bulk availability through registrar
            $module = $registrar->getModuleInstance();
            $apiResult = $module->checkBulkAvailability($uncachedDomains);

            if ($apiResult['success']) {
                foreach ($apiResult['results'] as $domainResult) {
                    $domain = $domainResult['domain'];

                    // Cache result
                    $cacheKey = "domain_availability:{$domain}";
                    Cache::put($cacheKey, [
                        'available' => $domainResult['available'],
                        'premium' => $domainResult['premium'] ?? false,
                        'premium_price' => $domainResult['premium_price'] ?? null,
                    ], 3600);

                    // Store in database
                    DB::table('domain_availability_cache')->updateOrInsert(
                        ['domain_name' => $domain],
                        [
                            'tld' => $this->getTld($domain),
                            'is_available' => $domainResult['available'],
                            'is_premium' => $domainResult['premium'] ?? false,
                            'premium_price' => $domainResult['premium_price'] ?? null,
                            'checked_at' => now(),
                            'expires_at' => now()->addHour(),
                            'updated_at' => now(),
                        ]
                    );

                    $results[] = array_merge($domainResult, ['cached' => false]);
                }
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
     * Generate domain suggestions
     */
    public function generateSuggestions(string $keyword, int $limit = 10): array
    {
        $suggestions = [];
        $tlds = ['.com', '.net', '.org', '.io', '.co', '.app', '.dev', '.tech', '.store', '.online'];

        $keyword = preg_replace('/[^a-z0-9]/i', '', strtolower($keyword));

        // Basic suggestions
        foreach ($tlds as $tld) {
            $suggestions[] = $keyword . $tld;
        }

        // Add variations
        $prefixes = ['get', 'try', 'my', 'the'];
        $suffixes = ['app', 'hq', 'hub', 'zone', 'pro'];

        foreach (array_slice($prefixes, 0, 2) as $prefix) {
            $suggestions[] = $prefix . $keyword . '.com';
        }

        foreach (array_slice($suffixes, 0, 2) as $suffix) {
            $suggestions[] = $keyword . $suffix . '.com';
        }

        // Limit results
        $suggestions = array_slice(array_unique($suggestions), 0, $limit);

        // Check availability for suggestions
        return $this->checkBulkAvailability($suggestions);
    }

    /**
     * Get TLD from domain
     */
    protected function getTld(string $domain): string
    {
        $parts = explode('.', $domain);
        return '.' . end($parts);
    }

    /**
     * Clear cache for a domain
     */
    public function clearCache(string $domain): void
    {
        $domain = strtolower(trim($domain));
        Cache::forget("domain_availability:{$domain}");
        DB::table('domain_availability_cache')->where('domain_name', $domain)->delete();
    }

    /**
     * Clear all expired cache entries
     */
    public function clearExpiredCache(): int
    {
        return DB::table('domain_availability_cache')
            ->where('expires_at', '<', now())
            ->delete();
    }
}
