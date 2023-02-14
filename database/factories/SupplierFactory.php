<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory {
    public function definition(): array {
        return [
            'company_id' => 1,
            'name'       => fake()->company() . ' Supplies',
            'email'      => fake()->unique()->companyEmail(),
            'phone'      => fake()->phoneNumber(),
            'address'    => fake()->address(),
            'status'     => 1,
        ];
    }
}
