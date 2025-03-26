<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sup_name' => $this->faker->company,
            'sup_address' => $this->faker->address,
            'sup_phone' => $this->faker->phoneNumber,
            'sup_email' => $this->faker->unique()->safeEmail,
            'sup_district' => $this->faker->randomElement([
                'Balaka', 'Blantyre', 'Chikwawa', 'Chiradzulu', 'Chitipa', 'Dedza', 'Dowa', 'Karonga',
                'Kasungu', 'Likoma', 'Lilongwe', 'Machinga', 'Mangochi', 'Mchinji', 'Mulanje', 'Mwanza',
                'Mzimba', 'Neno', 'Nkhata Bay', 'Nkhotakota', 'Nsanje', 'Ntcheu', 'Ntchisi', 'Phalombe',
                'Rumphi', 'Salima', 'Thyolo', 'Zomba'
            ]),
            'sup_type' => $this->faker->randomElement(['company', 'individual', 'government']),
            'sup_tax_id' => $this->faker->unique()->numerify('MW########'),
            'sup_contact_person' => $this->faker->name,
            'sup_contact_phone' => $this->faker->phoneNumber,
            'sup_bank_details' => $this->faker->bankAccountNumber,
            'sup_registration_number' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
