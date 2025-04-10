<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Software>
 */
class SoftwareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soft_name' => $this->faker->word(), // Generates a random word for the software name
            'soft_version' => $this->faker->numerify('v##.##'), // Generates a version like "v1.00"
            'soft_category' => $this->faker->word(), // A random category name
            'soft_desc' => $this->faker->sentence(), // A random sentence as the description
            'sup_id' => $this->faker->numberBetween(1, 10), // Generates a random supplier ID (Assuming the range 1-100)
            'soft_date_purchased' => $this->faker->date(), // Random purchase date
            'soft_license_type' => $this->faker->randomElement(['Free', 'Paid', 'Trial']), // Random license type
            'soft_license_from' => $this->faker->date(), // Random start date for license
            'soft_license_to' => $this->faker->date(), // Random end date for license
            'soft_license' => $this->faker->word(), // Random end date for license
        ];
    }
}
