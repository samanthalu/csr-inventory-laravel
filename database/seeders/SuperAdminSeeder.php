<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@inventory.csrunima.mw'],
            [
                'name'                => 'Administrator',
                'user_type'           => User::TYPE_ADMIN,
                'legacy_permissions'  => User::PERMISSION_READ
                                       | User::PERMISSION_CREATE
                                       | User::PERMISSION_EDIT
                                       | User::PERMISSION_DELETE,
                'password'            => Hash::make('Admin@1234'),
                'email_verified_at'   => now(),
            ]
        );

        if (!$user->hasRole('super-admin')) {
            $user->assignRole('super-admin');
        }

        $this->command->info("Super admin ready: {$user->email}");
    }
}
