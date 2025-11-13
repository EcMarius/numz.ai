<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Dashboard
            ['name' => 'view-dashboard', 'description' => 'View admin dashboard', 'group' => 'dashboard'],

            // Users
            ['name' => 'view-users', 'description' => 'View users', 'group' => 'users'],
            ['name' => 'create-users', 'description' => 'Create new users', 'group' => 'users'],
            ['name' => 'edit-users', 'description' => 'Edit users', 'group' => 'users'],
            ['name' => 'delete-users', 'description' => 'Delete users', 'group' => 'users'],

            // Roles & Permissions
            ['name' => 'view-roles', 'description' => 'View roles', 'group' => 'roles'],
            ['name' => 'create-roles', 'description' => 'Create new roles', 'group' => 'roles'],
            ['name' => 'edit-roles', 'description' => 'Edit roles', 'group' => 'roles'],
            ['name' => 'delete-roles', 'description' => 'Delete roles', 'group' => 'roles'],

            // Products
            ['name' => 'view-products', 'description' => 'View products', 'group' => 'products'],
            ['name' => 'create-products', 'description' => 'Create new products', 'group' => 'products'],
            ['name' => 'edit-products', 'description' => 'Edit products', 'group' => 'products'],
            ['name' => 'delete-products', 'description' => 'Delete products', 'group' => 'products'],

            // Orders
            ['name' => 'view-orders', 'description' => 'View orders', 'group' => 'orders'],
            ['name' => 'create-orders', 'description' => 'Create new orders', 'group' => 'orders'],
            ['name' => 'edit-orders', 'description' => 'Edit orders', 'group' => 'orders'],
            ['name' => 'delete-orders', 'description' => 'Delete orders', 'group' => 'orders'],

            // Invoices
            ['name' => 'view-invoices', 'description' => 'View invoices', 'group' => 'invoices'],
            ['name' => 'create-invoices', 'description' => 'Create new invoices', 'group' => 'invoices'],
            ['name' => 'edit-invoices', 'description' => 'Edit invoices', 'group' => 'invoices'],
            ['name' => 'delete-invoices', 'description' => 'Delete invoices', 'group' => 'invoices'],

            // Subscriptions
            ['name' => 'view-subscriptions', 'description' => 'View subscriptions', 'group' => 'subscriptions'],
            ['name' => 'edit-subscriptions', 'description' => 'Edit subscriptions', 'group' => 'subscriptions'],
            ['name' => 'cancel-subscriptions', 'description' => 'Cancel subscriptions', 'group' => 'subscriptions'],

            // Support Tickets
            ['name' => 'view-support', 'description' => 'View support tickets', 'group' => 'support'],
            ['name' => 'create-support', 'description' => 'Create support tickets', 'group' => 'support'],
            ['name' => 'edit-support', 'description' => 'Edit support tickets', 'group' => 'support'],
            ['name' => 'delete-support', 'description' => 'Delete support tickets', 'group' => 'support'],

            // Settings
            ['name' => 'view-settings', 'description' => 'View system settings', 'group' => 'settings'],
            ['name' => 'edit-settings', 'description' => 'Edit system settings', 'group' => 'settings'],

            // Reports
            ['name' => 'view-reports', 'description' => 'View reports', 'group' => 'reports'],
            ['name' => 'export-data', 'description' => 'Export data', 'group' => 'reports'],

            // API
            ['name' => 'manage-api', 'description' => 'Manage API credentials', 'group' => 'api'],

            // Domains
            ['name' => 'view-domains', 'description' => 'View domains', 'group' => 'domains'],
            ['name' => 'create-domains', 'description' => 'Create domains', 'group' => 'domains'],
            ['name' => 'edit-domains', 'description' => 'Edit domains', 'group' => 'domains'],
            ['name' => 'delete-domains', 'description' => 'Delete domains', 'group' => 'domains'],

            // Hosting
            ['name' => 'view-hosting', 'description' => 'View hosting services', 'group' => 'hosting'],
            ['name' => 'create-hosting', 'description' => 'Create hosting services', 'group' => 'hosting'],
            ['name' => 'edit-hosting', 'description' => 'Edit hosting services', 'group' => 'hosting'],
            ['name' => 'delete-hosting', 'description' => 'Delete hosting services', 'group' => 'hosting'],

            // Resellers
            ['name' => 'view-resellers', 'description' => 'View resellers', 'group' => 'resellers'],
            ['name' => 'create-resellers', 'description' => 'Create resellers', 'group' => 'resellers'],
            ['name' => 'edit-resellers', 'description' => 'Edit resellers', 'group' => 'resellers'],
            ['name' => 'delete-resellers', 'description' => 'Delete resellers', 'group' => 'resellers'],

            // Affiliates
            ['name' => 'view-affiliates', 'description' => 'View affiliates', 'group' => 'affiliates'],
            ['name' => 'create-affiliates', 'description' => 'Create affiliates', 'group' => 'affiliates'],
            ['name' => 'edit-affiliates', 'description' => 'Edit affiliates', 'group' => 'affiliates'],
            ['name' => 'delete-affiliates', 'description' => 'Delete affiliates', 'group' => 'affiliates'],

            // Activity Logs
            ['name' => 'view-activity-logs', 'description' => 'View activity logs', 'group' => 'logs'],
            ['name' => 'view-audit-logs', 'description' => 'View audit logs', 'group' => 'logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'description' => $permission['description'],
                    'group' => $permission['group'],
                    'is_system_permission' => true,
                ]
            );
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            [
                'description' => 'Super Administrator with full access',
                'is_system_role' => true,
                'priority' => 1,
            ]
        );

        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            [
                'description' => 'Administrator with most permissions',
                'is_system_role' => true,
                'priority' => 10,
            ]
        );

        $support = Role::firstOrCreate(
            ['name' => 'support', 'guard_name' => 'web'],
            [
                'description' => 'Support staff with limited access',
                'is_system_role' => true,
                'priority' => 20,
            ]
        );

        $client = Role::firstOrCreate(
            ['name' => 'client', 'guard_name' => 'web'],
            [
                'description' => 'Client user with basic access',
                'is_system_role' => true,
                'priority' => 30,
            ]
        );

        $registered = Role::firstOrCreate(
            ['name' => 'registered', 'guard_name' => 'web'],
            [
                'description' => 'Registered user',
                'is_system_role' => true,
                'priority' => 100,
            ]
        );

        // Assign permissions to roles
        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions([
            'view-dashboard',
            'view-users', 'create-users', 'edit-users',
            'view-products', 'create-products', 'edit-products',
            'view-orders', 'create-orders', 'edit-orders',
            'view-invoices', 'create-invoices', 'edit-invoices',
            'view-subscriptions', 'edit-subscriptions', 'cancel-subscriptions',
            'view-support', 'create-support', 'edit-support',
            'view-settings',
            'view-reports', 'export-data',
            'view-domains', 'create-domains', 'edit-domains',
            'view-hosting', 'create-hosting', 'edit-hosting',
            'view-activity-logs',
        ]);

        $support->syncPermissions([
            'view-dashboard',
            'view-users',
            'view-orders',
            'view-invoices',
            'view-subscriptions',
            'view-support', 'create-support', 'edit-support',
            'view-reports',
        ]);

        $client->syncPermissions([
            'view-support', 'create-support',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
