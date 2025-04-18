<?php

namespace Database\Seeders;

use App\Models\StaffProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        StaffProduct::factory()->count(50)->create();
    }
}
