<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => $this->faker->randomElement(['admin', 'coach', 'stagiaire']),
            'telephone' => $this->faker->phoneNumber(),
            'photo' => null,
            'promotion' => $this->faker->optional()->word(),
            'date_debut' => $this->faker->optional()->date(),
            'date_fin' => $this->faker->optional()->date(),
            'est_actif' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indiquer que l'utilisateur est un admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indiquer que l'utilisateur est un coach.
     */
    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'coach',
        ]);
    }

    /**
     * Indiquer que l'utilisateur est un stagiaire.
     */
    public function stagiaire(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'stagiaire',
        ]);
    }
}