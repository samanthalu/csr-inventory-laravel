<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prod_name' => $this->faker->randomElement(['Tablet', 'Phone', 'Recorder', 'Laptop','Desktop', 'Chargers']), // Random product name
            'prod_desc' => $this->faker->sentence(10), // Random product description
            'prod_cost' => $this->faker->randomFloat(2, 1, 1000), // Random cost, 2 decimal points, between 1 and 1000
            'prod_quantity' => $this->faker->numberBetween(1, 100), // Random quantity between 1 and 100
            'prod_serial_num' => $this->faker->unique()->bothify('??-#####'), // Random serial number
            'prod_tag_number' => $this->faker->regexify('[A-Za-z]{10}'), // Optional tag number
            'prod_model_number' => $this->faker->optional()->word, // Optional model number
            'prod_batch_number' => $this->faker->optional()->word, // Optional batch number
            'prod_other_identifier' => $this->faker->optional()->word, // Optional other identifier
            'prod_quantity_measure' => $this->faker->randomElement(['kg', 'bags', 'litres', 'items', 'meters']), // Optional quantity measure
            'prod_purchase_date' => $this->faker->date, // Optional purchase date
            'cat_id' => $this->faker->numberBetween(1, 10), // Random category ID (nullable)
            'sup_id' => $this->faker->numberBetween(1, 10), // Random supplier ID (nullable)
            'order_id' => $this->faker->numberBetween(1, 10), // Random order ID (nullable)
            'user_id' => \App\Models\User::pluck('id')->random(), // Random user ID (nullable)
            'prod_notes' => $this->faker->optional()->sentence(5), // Optional notes
            'prod_warranty_expire' => $this->faker->date, // Optional warranty expiration date
            'prod_condition' => $this->faker->randomElement(['New', 'Used', 'Refurbished', 'Damaged']), // Optional condition
            'prod_current_status' => $this->faker->randomElement(['Available', 'Hired out', 'Disposed', 'Stolen', 'Obsolete']), // Optional current status
            'created_at' => $this->faker->datetime,
            'updated_at' => $this->faker->datetime,
        ];
    }
}
