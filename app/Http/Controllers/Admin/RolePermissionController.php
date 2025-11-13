<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->middleware(['auth', 'role:super-admin|admin']);
        $this->activityLogger = $activityLogger;
    }

    /**
     * Display roles list
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->get();

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show create role form
     */
    public function create()
    {
        $permissions = Permission::all();
        $groups = Permission::getGroups();

        return view('admin.roles.create', compact('permissions', 'groups'));
    }

    /**
     * Store new role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description,
            'is_system_role' => false,
            'priority' => 500,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $this->activityLogger->logCreate($role, "Created role: {$role->name}");

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Show edit role form
     */
    public function edit(Role $role)
    {
        if ($role->isSystemRole()) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be edited.');
        }

        $permissions = Permission::all();
        $groups = Permission::getGroups();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'groups', 'rolePermissions'));
    }

    /**
     * Update role
     */
    public function update(Request $request, Role $role)
    {
        if ($role->isSystemRole()) {
            return back()->with('error', 'System roles cannot be edited.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $oldValues = $role->getAttributes();

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $this->activityLogger->logUpdate($role, [
            'old' => $oldValues,
            'new' => $role->getAttributes(),
        ], "Updated role: {$role->name}");

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Delete role
     */
    public function destroy(Role $role)
    {
        if ($role->isSystemRole()) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role with assigned users.');
        }

        $roleName = $role->name;
        $role->delete();

        $this->activityLogger->logDelete($role, "Deleted role: {$roleName}");

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Display permissions list
     */
    public function permissions()
    {
        $permissions = Permission::all();
        $groupedPermissions = Permission::getGroupedPermissions();

        return view('admin.permissions.index', compact('permissions', 'groupedPermissions'));
    }

    /**
     * Show create permission form
     */
    public function createPermission()
    {
        $groups = Permission::getGroups();

        return view('admin.permissions.create', compact('groups'));
    }

    /**
     * Store new permission
     */
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string',
            'group' => 'nullable|string|max:50',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description,
            'group' => $request->group,
            'is_system_permission' => false,
        ]);

        $this->activityLogger->logCreate($permission, "Created permission: {$permission->name}");

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        $user->assignRole($role);

        $this->activityLogger->logPermissionChange(
            $user,
            'role_assigned',
            ['role' => $role->name]
        );

        return back()->with('success', "Role '{$role->name}' assigned to {$user->name}.");
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($request->user_id);
        $role = Role::findOrFail($request->role_id);

        $user->removeRole($role);

        $this->activityLogger->logPermissionChange(
            $user,
            'role_removed',
            ['role' => $role->name]
        );

        return back()->with('success', "Role '{$role->name}' removed from {$user->name}.");
    }
}
