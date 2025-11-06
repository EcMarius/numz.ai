<?php

namespace App\Numz\Modules\DomainRegistrars;

interface RegistrarInterface
{
    /**
     * Test connection to registrar
     */
    public function testConnection(): array;

    /**
     * Check domain availability
     */
    public function checkAvailability(string $domain): array;

    /**
     * Check availability for multiple domains
     */
    public function checkBulkAvailability(array $domains): array;

    /**
     * Register a domain
     */
    public function registerDomain(string $domain, array $contactInfo, int $years = 1, array $options = []): array;

    /**
     * Renew a domain
     */
    public function renewDomain(string $domain, int $years = 1): array;

    /**
     * Transfer a domain
     */
    public function transferDomain(string $domain, string $eppCode, int $years = 1): array;

    /**
     * Get domain details
     */
    public function getDomainDetails(string $domain): array;

    /**
     * Update nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): array;

    /**
     * Get nameservers
     */
    public function getNameservers(string $domain): array;

    /**
     * Enable domain lock
     */
    public function enableLock(string $domain): array;

    /**
     * Disable domain lock
     */
    public function disableLock(string $domain): array;

    /**
     * Get EPP/Auth code
     */
    public function getEppCode(string $domain): array;

    /**
     * Enable WHOIS privacy
     */
    public function enableWhoisPrivacy(string $domain): array;

    /**
     * Disable WHOIS privacy
     */
    public function disableWhoisPrivacy(string $domain): array;

    /**
     * Get WHOIS information
     */
    public function getWhoisInfo(string $domain): array;

    /**
     * Update contact information
     */
    public function updateContactInfo(string $domain, array $contactInfo, string $contactType = 'registrant'): array;

    /**
     * Get contact information
     */
    public function getContactInfo(string $domain, string $contactType = 'registrant'): array;

    /**
     * Sync pricing from registrar
     */
    public function syncPricing(): array;

    /**
     * Get TLD pricing
     */
    public function getTldPricing(string $tld): array;

    /**
     * Create DNS zone
     */
    public function createDnsZone(string $domain): array;

    /**
     * Get DNS records
     */
    public function getDnsRecords(string $domain): array;

    /**
     * Add DNS record
     */
    public function addDnsRecord(string $domain, string $type, string $name, string $content, int $ttl = 3600, ?int $priority = null): array;

    /**
     * Update DNS record
     */
    public function updateDnsRecord(string $domain, int $recordId, array $data): array;

    /**
     * Delete DNS record
     */
    public function deleteDnsRecord(string $domain, int $recordId): array;
}
