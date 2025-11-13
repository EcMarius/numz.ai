<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'group',
        'is_system_permission',
    ];

    protected $casts = [
        'is_system_permission' => 'boolean',
    ];

    /**
     * Permission groups for organization
     */
    const GROUP_USERS = 'users';
    const GROUP_ROLES = 'roles';
    const GROUP_PRODUCTS = 'products';
    const GROUP_ORDERS = 'orders';
    const GROUP_INVOICES = 'invoices';
    const GROUP_SUBSCRIPTIONS = 'subscriptions';
    const GROUP_SUPPORT = 'support';
    const GROUP_SETTINGS = 'settings';
    const GROUP_REPORTS = 'reports';
    const GROUP_API = 'api';
    const GROUP_DOMAINS = 'domains';
    const GROUP_HOSTING = 'hosting';
    const GROUP_RESELLERS = 'resellers';
    const GROUP_AFFILIATES = 'affiliates';

    /**
     * Predefined permissions
     */
    const VIEW_DASHBOARD = 'view-dashboard';
    const VIEW_USERS = 'view-users';
    const CREATE_USERS = 'create-users';
    const EDIT_USERS = 'edit-users';
    const DELETE_USERS = 'delete-users';
    const VIEW_ROLES = 'view-roles';
    const CREATE_ROLES = 'create-roles';
    const EDIT_ROLES = 'edit-roles';
    const DELETE_ROLES = 'delete-roles';
    const VIEW_PRODUCTS = 'view-products';
    const CREATE_PRODUCTS = 'create-products';
    const EDIT_PRODUCTS = 'edit-products';
    const DELETE_PRODUCTS = 'delete-products';
    const VIEW_ORDERS = 'view-orders';
    const CREATE_ORDERS = 'create-orders';
    const EDIT_ORDERS = 'edit-orders';
    const DELETE_ORDERS = 'delete-orders';
    const VIEW_INVOICES = 'view-invoices';
    const CREATE_INVOICES = 'create-invoices';
    const EDIT_INVOICES = 'edit-invoices';
    const DELETE_INVOICES = 'delete-invoices';
    const VIEW_SUPPORT = 'view-support';
    const CREATE_SUPPORT = 'create-support';
    const EDIT_SUPPORT = 'edit-support';
    const DELETE_SUPPORT = 'delete-support';
    const VIEW_SETTINGS = 'view-settings';
    const EDIT_SETTINGS = 'edit-settings';
    const VIEW_REPORTS = 'view-reports';
    const EXPORT_DATA = 'export-data';
    const MANAGE_API = 'manage-api';

    /**
     * Get roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            'permission_id',
            'role_id'
        );
    }

    /**
     * Check if permission is a system permission
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission ?? false;
    }

    /**
     * Scope to get permissions by group
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope to get only system permissions
     */
    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Scope to get only custom permissions
     */
    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false)->orWhereNull('is_system_permission');
    }

    /**
     * Get all available permission groups
     */
    public static function getGroups(): array
    {
        return [
            self::GROUP_USERS => 'User Management',
            self::GROUP_ROLES => 'Role & Permission Management',
            self::GROUP_PRODUCTS => 'Product Management',
            self::GROUP_ORDERS => 'Order Management',
            self::GROUP_INVOICES => 'Invoice Management',
            self::GROUP_SUBSCRIPTIONS => 'Subscription Management',
            self::GROUP_SUPPORT => 'Support Tickets',
            self::GROUP_SETTINGS => 'System Settings',
            self::GROUP_REPORTS => 'Reports & Analytics',
            self::GROUP_API => 'API Management',
            self::GROUP_DOMAINS => 'Domain Management',
            self::GROUP_HOSTING => 'Hosting Management',
            self::GROUP_RESELLERS => 'Reseller Management',
            self::GROUP_AFFILIATES => 'Affiliate Management',
        ];
    }

    /**
     * Get grouped permissions
     */
    public static function getGroupedPermissions(): array
    {
        $permissions = self::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $group = $permission->group ?? 'other';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get permission display name
     */
    public function getDisplayName(): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $this->name));
    }
}
