<?php

namespace Database\Seeders;
use App\Models\Software;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SoftwareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //// Create 10 software records
        Software::factory()->count(10)->create();
    }
}
