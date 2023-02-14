<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory {
    public function definition(): array {
        return [
            'company_id'    => 1,
            'name'          => fake()->name(),
            'email'         => fake()->unique()->safeEmail(),
            'password'      => Hash::make('password'),
            'phone'         => fake()->phoneNumber(),
            'status'        => 1,
        ];
    }
    public function admin(): static {
        return $this->state(['is_superadmin' => true]);
    }
}
