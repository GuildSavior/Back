<?php

namespace Database\Factories;

use App\Models\GuildMemberDkp;
use App\Models\User;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuildMemberDkp>
 */
class GuildMemberDkpFactory extends Factory
{
    protected $model = GuildMemberDkp::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guild_id' => Guild::factory(),
            'dkp' => $this->faker->numberBetween(0, 1000),
            'events_joined' => $this->faker->numberBetween(0, 50),
        ];
    }
}
