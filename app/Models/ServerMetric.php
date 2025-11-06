<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosting_server_id',
        'recorded_at',
        'cpu_usage',
        'memory_usage',
        'disk_used',
        'disk_total',
        'bandwidth_used',
        'active_connections',
        'load_average',
        'response_time',
        'additional_metrics',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'cpu_usage' => 'decimal:2',
        'memory_usage' => 'decimal:2',
        'load_average' => 'decimal:2',
        'additional_metrics' => 'array',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(HostingServer::class, 'hosting_server_id');
    }

    /**
     * Get disk usage percentage
     */
    public function getDiskUsagePercentageAttribute(): float
    {
        if ($this->disk_total === 0) {
            return 0;
        }

        return round(($this->disk_used / $this->disk_total) * 100, 2);
    }

    /**
     * Check if server is healthy
     */
    public function isHealthy(): bool
    {
        return $this->cpu_usage < 80 && $this->memory_usage < 80 && $this->getDiskUsagePercentageAttribute() < 90;
    }
}
