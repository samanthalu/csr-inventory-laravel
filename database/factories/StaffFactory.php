<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_first_name' => $this->faker->firstName,
            'staff_last_name' => $this->faker->lastName,
            'staff_email' => $this->faker->unique()->safeEmail,
            'staff_phone' => $this->faker->phoneNumber,
            'staff_position' => $this->faker->randomElement([
                'Manager',
                'Developer',
                'Analyst',
                'Coordinator',
                'Specialist',
                'Assistant'
            ]),
            'staff_status' => $this->faker->randomElement(['active', 'inactive', 'on_leave']),
        ];
    }
}
