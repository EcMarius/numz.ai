<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'dns_zone_id',
        'name',
        'type',
        'content',
        'ttl',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function dnsZone(): BelongsTo
    {
        return $this->belongsTo(DnsZone::class);
    }

    /**
     * Get available DNS record types
     */
    public static function getAvailableTypes(): array
    {
        return [
            'A' => 'A - IPv4 Address',
            'AAAA' => 'AAAA - IPv6 Address',
            'CNAME' => 'CNAME - Canonical Name',
            'MX' => 'MX - Mail Exchange',
            'TXT' => 'TXT - Text',
            'NS' => 'NS - Name Server',
            'SRV' => 'SRV - Service',
            'CAA' => 'CAA - Certification Authority Authorization',
        ];
    }

    /**
     * Validate record content based on type
     */
    public function validateContent(): bool
    {
        return match($this->type) {
            'A' => filter_var($this->content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'AAAA' => filter_var($this->content, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            'MX', 'CNAME', 'NS' => !empty($this->content),
            'TXT' => strlen($this->content) <= 255,
            default => true,
        };
    }
}
