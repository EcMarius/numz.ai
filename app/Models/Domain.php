<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'domain_registrar_id',
        'domain_name',
        'tld',
        'sld',
        'status',
        'registration_type',
        'registered_at',
        'expires_at',
        'transferred_at',
        'cancelled_at',
        'auto_renew',
        'renewal_period',
        'registration_price',
        'renewal_price',
        'transfer_price',
        'currency',
        'whois_privacy',
        'domain_lock',
        'auto_renew_whois_privacy',
        'nameservers',
        'epp_code',
        'registrant_contact',
        'admin_contact',
        'tech_contact',
        'billing_contact',
        'remote_id',
        'remote_data',
        'notes',
    ];

    protected $casts = [
        'registered_at' => 'date',
        'expires_at' => 'date',
        'transferred_at' => 'date',
        'cancelled_at' => 'date',
        'auto_renew' => 'boolean',
        'whois_privacy' => 'boolean',
        'domain_lock' => 'boolean',
        'auto_renew_whois_privacy' => 'boolean',
        'nameservers' => 'array',
        'registrant_contact' => 'array',
        'admin_contact' => 'array',
        'tech_contact' => 'array',
        'billing_contact' => 'array',
        'remote_data' => 'array',
    ];

    /**
     * Get the user that owns the domain
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the domain registrar
     */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(DomainRegistrar::class, 'domain_registrar_id');
    }

    /**
     * Get domain renewals
     */
    public function renewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class);
    }

    /**
     * Get domain transfers
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(DomainTransfer::class);
    }

    /**
     * Get DNS zone
     */
    public function dnsZone(): HasMany
    {
        return $this->hasMany(DnsZone::class);
    }

    /**
     * Get WHOIS privacy orders
     */
    public function whoisPrivacyOrders(): HasMany
    {
        return $this->hasMany(WhoisPrivacyOrder::class);
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if domain is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if domain is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        $daysUntilExpiration = $this->days_until_expiration;
        return $daysUntilExpiration !== null && $daysUntilExpiration <= $days && $daysUntilExpiration > 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'expired' => 'danger',
            'cancelled' => 'gray',
            'transferred' => 'info',
            default => 'gray',
        };
    }

    /**
     * Parse domain name into SLD and TLD
     */
    public static function parseDomainName(string $domainName): array
    {
        $parts = explode('.', $domainName);

        if (count($parts) < 2) {
            throw new \InvalidArgumentException('Invalid domain name');
        }

        // Handle common two-part TLDs like .co.uk, .com.au
        $twoPartTlds = ['co.uk', 'com.au', 'co.nz', 'com.br', 'co.za', 'co.in'];

        if (count($parts) >= 3) {
            $lastTwoParts = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            if (in_array($lastTwoParts, $twoPartTlds)) {
                $tld = '.' . $lastTwoParts;
                $sld = implode('.', array_slice($parts, 0, -2));
                return ['sld' => $sld, 'tld' => $tld];
            }
        }

        $tld = '.' . end($parts);
        $sld = implode('.', array_slice($parts, 0, -1));

        return ['sld' => $sld, 'tld' => $tld];
    }

    /**
     * Create a new domain from domain name
     */
    public static function createFromDomainName(string $domainName, int $userId, array $additionalData = []): self
    {
        $parsed = self::parseDomainName($domainName);

        return self::create(array_merge([
            'user_id' => $userId,
            'domain_name' => strtolower($domainName),
            'sld' => $parsed['sld'],
            'tld' => $parsed['tld'],
            'status' => 'pending',
        ], $additionalData));
    }

    /**
     * Renew domain
     */
    public function renew(int $years = 1, ?float $amount = null): DomainRenewal
    {
        $amount = $amount ?? $this->renewal_price * $years;

        $renewal = $this->renewals()->create([
            'user_id' => $this->user_id,
            'years_renewed' => $years,
            'previous_expiry_date' => $this->expires_at,
            'new_expiry_date' => $this->expires_at->copy()->addYears($years),
            'amount' => $amount,
            'currency' => $this->currency,
            'status' => 'pending',
        ]);

        // Update domain expiry date
        $this->update([
            'expires_at' => $renewal->new_expiry_date,
        ]);

        return $renewal;
    }

    /**
     * Enable WHOIS privacy
     */
    public function enableWhoisPrivacy(float $price): WhoisPrivacyOrder
    {
        $order = $this->whoisPrivacyOrders()->create([
            'user_id' => $this->user_id,
            'status' => 'pending',
            'price' => $price,
            'currency' => $this->currency,
            'start_date' => now(),
            'end_date' => $this->expires_at,
            'auto_renew' => $this->auto_renew_whois_privacy,
        ]);

        $this->update(['whois_privacy' => true]);

        return $order;
    }

    /**
     * Disable WHOIS privacy
     */
    public function disableWhoisPrivacy(): void
    {
        $this->whoisPrivacyOrders()
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $this->update(['whois_privacy' => false]);
    }

    /**
     * Update nameservers
     */
    public function updateNameservers(array $nameservers): void
    {
        $this->update(['nameservers' => $nameservers]);

        // TODO: Call registrar API to update nameservers
    }

    /**
     * Get expiring domains
     */
    public static function getExpiringDomains(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->where('auto_renew', false)
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Get expired domains
     */
    public static function getExpiredDomains(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->where('expires_at', '<', now())
            ->orderBy('expires_at')
            ->get();
    }
}
