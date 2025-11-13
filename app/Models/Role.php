<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'is_system_role',
        'priority',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Predefined system roles
     */
    const SUPER_ADMIN = 'super-admin';
    const ADMIN = 'admin';
    const SUPPORT = 'support';
    const CLIENT = 'client';
    const REGISTERED = 'registered';

    /**
     * Get all permissions for this role
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * Get users with this role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('permission.table_names.model_has_roles'),
            'role_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Check if role is a system role
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role ?? false;
    }

    /**
     * Scope to get only system roles
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope to get only custom roles
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false)->orWhereNull('is_system_role');
    }

    /**
     * Get role priority (lower number = higher priority)
     */
    public function getPriority(): int
    {
        return $this->priority ?? 999;
    }

    /**
     * Check if this role has higher priority than another role
     */
    public function hasHigherPriorityThan(Role $role): bool
    {
        return $this->getPriority() < $role->getPriority();
    }

    /**
     * Get role color for UI display
     */
    public function getColorAttribute(): string
    {
        return match($this->name) {
            self::SUPER_ADMIN => 'red',
            self::ADMIN => 'purple',
            self::SUPPORT => 'blue',
            self::CLIENT => 'green',
            default => 'gray',
        };
    }

    /**
     * Get role badge HTML
     */
    public function getBadgeHtml(): string
    {
        $color = $this->color;
        return "<span class='badge badge-{$color}'>{$this->name}</span>";
    }
}
