<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model= User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,//fake()->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'fakePassword',//'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'role' => $this->faker->randomElement(['student','teacher']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /*public function hasRole(string $role): static
    {
        return $this->state(function (array $attributes) use ($role) {
            return [
                'role' => $role,
            ];
        });
    }*/
}
