<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Guild;

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
            'name' => $this->faker->unique()->company(), // Génère un nom unique pour la guilde
            'description' => $this->faker->text(),       // Génère une description aléatoire
            'creation_date' => $this->faker->date(),     // Génère une date aléatoire pour la création
        ];
    }
}
