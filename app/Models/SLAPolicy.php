<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SLAPolicy extends Model
{
    use HasFactory;

    protected $table = 'sla_policies';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
        'first_response_time',
        'resolution_time',
        'priority_multipliers',
        'working_hours',
        'holidays',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority_multipliers' => 'array',
        'working_hours' => 'array',
        'holidays' => 'array',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'sla_policy_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(SupportDepartment::class, 'sla_policy_id');
    }

    /**
     * Get response time for priority
     */
    public function getResponseTimeForPriority(string $priority): int
    {
        if ($this->priority_multipliers && isset($this->priority_multipliers[$priority])) {
            return (int) ($this->first_response_time * $this->priority_multipliers[$priority]);
        }

        return $this->first_response_time;
    }

    /**
     * Get resolution time for priority
     */
    public function getResolutionTimeForPriority(string $priority): int
    {
        if ($this->priority_multipliers && isset($this->priority_multipliers[$priority])) {
            return (int) ($this->resolution_time * $this->priority_multipliers[$priority]);
        }

        return $this->resolution_time;
    }

    /**
     * Calculate SLA breach time
     */
    public function calculateBreachTime(\Carbon\Carbon $startTime, string $priority, string $type = 'response'): \Carbon\Carbon
    {
        $minutes = $type === 'response'
            ? $this->getResponseTimeForPriority($priority)
            : $this->getResolutionTimeForPriority($priority);

        // If working hours are set, calculate considering business hours
        if ($this->working_hours) {
            return $this->calculateBusinessHours($startTime, $minutes);
        }

        return $startTime->copy()->addMinutes($minutes);
    }

    /**
     * Calculate business hours
     */
    private function calculateBusinessHours(\Carbon\Carbon $start, int $minutes): \Carbon\Carbon
    {
        // Simplified version - in production, this would handle weekends, holidays, etc.
        return $start->copy()->addMinutes($minutes);
    }

    /**
     * Set as default policy
     */
    public function setAsDefault(): void
    {
        self::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Get default policy
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }
}
