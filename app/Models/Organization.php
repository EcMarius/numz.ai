<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'address',
        'domain',
        'owner_id',
    ];

    /**
     * Get the owner of the organization
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all team members of the organization (including owner)
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    /**
     * Get only team members (excluding owner)
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id')
            ->where('team_role', 'member');
    }

    /**
     * Get available seats for the organization
     */
    public function getAvailableSeatsAttribute(): int
    {
        // subscription() is a relationship, not a method - need to use first()
        $subscription = $this->owner->subscription()->first();

        if (!$subscription) {
            return 0;
        }

        return ($subscription->seats_purchased ?? 0) - ($subscription->seats_used ?? 0);
    }

    /**
     * Get used seats count
     */
    public function getUsedSeatsAttribute(): int
    {
        return $this->teamMembers()->count();
    }

    /**
     * Get total seats purchased
     */
    public function getTotalSeatsAttribute(): int
    {
        // subscription() is a relationship, not a method - need to use first()
        $subscription = $this->owner->subscription()->first();

        return $subscription ? ($subscription->seats_purchased ?? 0) : 0;
    }
}
