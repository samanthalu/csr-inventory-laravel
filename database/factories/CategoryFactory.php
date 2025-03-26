<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->word;
        return [
            'cat_name' => ucfirst($name),
            'cat_desc' => $this->faker->sentence,
            'cat_hireable' => $this->faker->boolean,
            'cat_slug' => Str::slug($name),
            'cat_status' => $this->faker->randomElement([0, 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
