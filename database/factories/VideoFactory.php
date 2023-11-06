<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->name(),
            'description' => $this->faker->sentence,
            'link' => 'https://www.youtube.com/watch?v=p6Yw0Bx5dbw',
            'provider' => $this->faker->randomAscii(),
            'points' => $this->faker->randomFloat(2, 0, 5),
            'video_id' => $this->faker->unique()->word(),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
