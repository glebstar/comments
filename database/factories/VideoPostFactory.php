<?php

namespace Database\Factories;

use App\Models\VideoPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoPost>
 */
class VideoPostFactory extends Factory
{
    protected $model = VideoPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(8),
        ];
    }
}
