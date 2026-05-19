<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Software;
use App\Models\Staff;
use App\Models\StaffProduct;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Always run in production and development
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
        ]);

        // Dev-only fake data — skip in production
        if (app()->isLocal()) {
            User::factory()->create([
                'name'  => 'Test User',
                'email' => 'test@example.com',
            ]);

            Supplier::factory(20)->create();
            Category::factory(10)->create();
            Product::factory(50)->create();
            Software::factory()->count(10)->create();
            Staff::factory()->count(10)->create();
            Borrower::factory(40)->create();
            StaffProduct::factory(50)->create();
        }
    }
}
