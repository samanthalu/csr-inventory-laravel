<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\User;
Use App\Models\Product;
Use App\Models\Supplier;
Use App\Models\Category;
Use App\Models\Software;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
      

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Supplier::factory(20)->create();
        Category::factory(10)->create();
        Product::factory(50)->create();
        Borrower::factory((40))->create();
        Software::factory()->count(10)->create();
    }
}
