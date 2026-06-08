<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = collect(['view', 'create', 'update', 'delete'])
            ->map(fn($a) => Permission::firstOrCreate(['name' => "{$a}_fieldwork"]));

        // super-admin gets all via wildcard — just ensure ict and administration get access
        Role::findByName('ict')->givePermissionTo($perms);
        Role::findByName('administration')->givePermissionTo([
            'view_fieldwork', 'create_fieldwork', 'update_fieldwork',
        ]);
        Role::findByName('standard')->givePermissionTo(['view_fieldwork']);
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        foreach (['view', 'create', 'update', 'delete'] as $a) {
            Permission::findByName("{$a}_fieldwork")?->delete();
        }
    }
};
