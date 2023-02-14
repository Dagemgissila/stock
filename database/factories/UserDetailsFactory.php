<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDetailsFactory extends Factory {
    public function definition(): array {
        return [
            'company_id'   => 1,
            'user_id'      => 1,
            'avatar'       => null,
            'bio'          => fake()->sentence(),
            'date_of_birth'=> fake()->date('Y-m-d','2000-01-01'),
        ];
    }
}
