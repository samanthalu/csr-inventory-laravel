<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ──────────────────────────────────────────
        $resources = [
            'products', 'suppliers', 'categories',
            'hires', 'maintenance', 'disposal',
            'staff', 'users', 'hardware', 'fieldwork',
        ];

        foreach ($resources as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                Permission::firstOrCreate(['name' => "{$action}_{$resource}"]);
            }
        }

        // Single-action permissions
        Permission::firstOrCreate(['name' => 'view_reports']);
        Permission::firstOrCreate(['name' => 'view_audit_log']);
        Permission::firstOrCreate(['name' => 'manage_settings']);

        // ── Define roles ────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        $ict = Role::firstOrCreate(['name' => 'ict']);
        $ict->syncPermissions([
            'view_products',    'create_products',    'update_products',    'delete_products',
            'view_suppliers',   'create_suppliers',   'update_suppliers',   'delete_suppliers',
            'view_categories',  'create_categories',  'update_categories',  'delete_categories',
            'view_hires',       'create_hires',       'update_hires',       'delete_hires',
            'view_maintenance', 'create_maintenance', 'update_maintenance', 'delete_maintenance',
            'view_disposal',    'create_disposal',    'update_disposal',    'delete_disposal',
            'view_hardware',    'create_hardware',    'update_hardware',    'delete_hardware',
            'view_staff',
            'view_reports',
            'view_audit_log',
            'view_fieldwork', 'create_fieldwork', 'update_fieldwork', 'delete_fieldwork',
        ]);

        $administration = Role::firstOrCreate(['name' => 'administration']);
        $administration->syncPermissions([
            'view_products',
            'view_suppliers',
            'view_hires',    'create_hires',    'update_hires',    'delete_hires',
            'view_maintenance', 'create_maintenance', 'update_maintenance',
            'view_disposal',    'create_disposal',    'update_disposal',
            'view_hardware',
            'view_staff',
            'view_reports',
            'view_fieldwork', 'create_fieldwork', 'update_fieldwork',
        ]);

        $standard = Role::firstOrCreate(['name' => 'standard']);
        $standard->syncPermissions([
            'view_products',
            'view_hires',
            'view_maintenance',
            'view_disposal',
            'view_hardware',
            'view_reports',
            'view_fieldwork',
        ]);

        // ── Assign roles to existing users based on user_type ───────────────
        $map = [
            'admin'          => 'super-admin',
            'ict'            => 'ict',
            'administration' => 'administration',
            'standard'       => 'standard',
        ];

        foreach ($map as $userType => $roleName) {
            User::where('user_type', $userType)->get()->each(function ($user) use ($roleName) {
                if (!$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                }
            });
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
