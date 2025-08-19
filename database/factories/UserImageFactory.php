<?php

namespace Database\Factories;

use App\Models\UserImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserImageFactory extends Factory
{
    protected $model = UserImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->text(200),
            'filename' => $this->faker->uuid . '.jpg',
            'original_name' => 'test.jpg',
            'path' => 'public/user-images/1/test.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => $this->faker->numberBetween(100000, 1000000),
            'width' => 800,
            'height' => 600,
            'is_public' => $this->faker->boolean(80), // 80% de chance d'être public
        ];
    }
}
