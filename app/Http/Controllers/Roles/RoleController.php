<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Services\AuditLogger;

class RoleController extends Controller
{
    // GET /api/permissions — all permissions grouped by resource
    public function permissions()
    {
        $grouped = Permission::all()
            ->groupBy(function ($p) {
                // "view_products" → "products", "view_reports" → "reports"
                $parts = explode('_', $p->name, 2);
                return $parts[1] ?? $p->name;
            })
            ->map(fn($perms) => $perms->pluck('name')->values())
            ->sortKeys();

        return response()->json(['data' => $grouped]);
    }

    // GET /api/roles — all roles with their permissions
    public function index()
    {
        $roles = Role::with('permissions')->get()->map(fn($r) => [
            'id'          => $r->id,
            'name'        => $r->name,
            'permissions' => $r->permissions->pluck('name')->values(),
            'users_count' => User::role($r->name)->count(),
        ]);

        return response()->json(['data' => $roles]);
    }

    // POST /api/roles — create role
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        AuditLogger::log('role', 'created', "Role '{$role->name}' created", $role->id, null, ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()]);

        return response()->json([
            'message' => 'Role created successfully.',
            'data'    => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users_count' => 0,
            ],
        ], 201);
    }

    // PUT /api/roles/{id} — update name and/or permissions
    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super-admin') {
            return response()->json(['message' => 'The super-admin role cannot be modified.'], 403);
        }

        $request->validate([
            'name'        => "sometimes|string|unique:roles,name,{$id}",
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($request->filled('name')) {
            $role->name = $request->name;
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        $role->load('permissions');

        AuditLogger::log('role', 'updated', "Role '{$role->name}' updated", $role->id, null, ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'data'    => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users_count' => User::role($role->name)->count(),
            ],
        ]);
    }

    // DELETE /api/roles/{id}
    public function destroy(int $id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['super-admin', 'standard'])) {
            return response()->json(['message' => "The '{$role->name}' role cannot be deleted."], 403);
        }

        AuditLogger::log('role', 'deleted', "Role '{$role->name}' deleted", $role->id);
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully.']);
    }

    // GET /api/users/{id}/roles
    public function userRoles(int $userId)
    {
        $user = User::findOrFail($userId);
        return response()->json([
            'data' => [
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ]
        ]);
    }

    // POST /api/users/{id}/roles — sync roles for a user
    public function assignRoles(Request $request, int $userId)
    {
        $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::findOrFail($userId);
        $user->syncRoles($request->roles);

        AuditLogger::log('role', 'assigned', "Roles for user '{$user->name}' updated", $user->id, null, ['roles' => $request->roles]);

        return response()->json(['message' => 'Roles assigned successfully.']);
    }
}
