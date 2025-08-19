<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->text(200),
            'guild_id' => Guild::factory(),
            'event_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'location' => $this->faker->address(),
            'dkp_reward' => $this->faker->numberBetween(10, 100),
            'access_code' => $this->faker->unique()->regexify('[A-Z0-9]{6}'),
            'is_active' => true,
        ];
    }
}
