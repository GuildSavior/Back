<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'discord_id' => $this->faker->unique()->numerify('##########'),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'avatar' => $this->faker->imageUrl(100, 100),
            'remember_token' => Str::random(10),
            'refresh_token' => Str::random(40),
            'statut' => $this->faker->randomElement(['on', 'off']),
            'total_dkp' => $this->faker->numberBetween(0, 1000),
            'role_id' => Role::factory(), // ⭐ CRÉER UN RÔLE AUTOMATIQUEMENT
        ];
    }
}
