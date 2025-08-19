<?php

namespace Database\Factories;

use App\Models\GuildMember;
use App\Models\User;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuildMemberFactory extends Factory
{
    protected $model = GuildMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(['member', 'officer', 'leader']),
            'joined_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
