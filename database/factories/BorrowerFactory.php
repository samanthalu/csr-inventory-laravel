<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrower>
 */
class BorrowerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'staff_id' => $this->faker->numberBetween(1, 10),
            'pb_purpose' => $this->faker->sentence(3), // Purpose of borrowing
            'pb_date_from' => $this->faker->date(), // Start date of borrowing
            'pb_date_to' => $this->faker->date(), // Start date of borrowing
            'pb_with_accessories' => $this->faker->randomElement(['yes', 'no']), // Start date of borrowing
            // 'pb_prod_id' => \App\Models\Product::pluck('prod_id')->random(), //
            'pb_status' => $this->faker->randomElement(['returned', 'not-returned']),
        ];
    }
}
