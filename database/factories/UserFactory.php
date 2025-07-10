<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Guild;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;
    /**
     * Le nom du modèle associé à la factory.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Définir l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => 'chouba', 
            'discord_id' => '0',  // Génère un nom d'utilisateur unique
            'remember_token' => Str::random(10),
            'role_id' => 1,     // Associe un rôle aléatoire à partir des rôles existants
        ];
    }
}
