<?php

namespace Database\Factories;

use App\Models\Guild;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guild>
 */
class GuildFactory extends Factory
{
    /**
     * Le nom du modèle associé à la factory.
     *
     * @var string
     */
    protected $model = Guild::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company() . ' Guild', // Génère un nom unique pour la guilde
            'description' => $this->faker->text(200),       // Génère une description aléatoire
            'banner' => $this->faker->imageUrl(800, 200),
            'icon' => $this->faker->imageUrl(100, 100),
            'member_count' => $this->faker->numberBetween(1, 50),
            'region' => $this->faker->randomElement(['EU', 'NA', 'AS']),
            'creation_date' => $this->faker->date(),     // Génère une date aléatoire pour la création
            'owner_id' => User::factory(), // ⭐ CRÉER UN OWNER AUTOMATIQUEMENT
        ];
    }
}
