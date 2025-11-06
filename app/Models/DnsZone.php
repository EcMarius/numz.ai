<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DnsZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'user_id',
        'zone_name',
        'status',
        'serial',
        'refresh',
        'retry',
        'expire',
        'ttl',
        'primary_ns',
        'admin_email',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    /**
     * Increment serial number
     */
    public function incrementSerial(): void
    {
        // Format: YYYYMMDDnn where nn is daily increment
        $today = now()->format('Ymd');
        $currentSerial = (string) $this->serial;

        if (str_starts_with($currentSerial, $today)) {
            // Increment today's counter
            $counter = (int) substr($currentSerial, -2);
            $newSerial = (int) ($today . str_pad($counter + 1, 2, '0', STR_PAD_LEFT));
        } else {
            // Start new day
            $newSerial = (int) ($today . '01');
        }

        $this->update(['serial' => $newSerial]);
    }
}
