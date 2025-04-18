<?php

namespace Database\Factories;

use App\Models\Borrower;
use App\Models\Staff;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffProduct>
 */
class StaffProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'sp_staff_id' => Staff::factory(), // or ->random()->id for existing staff
            'sp_prod_id' => Product::factory(), // same here
            'sp_pb_id' =>  \App\Models\Borrower::pluck('pb_id')->random(), //
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
