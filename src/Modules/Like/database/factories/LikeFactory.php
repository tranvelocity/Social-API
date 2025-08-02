<?php

namespace Modules\Like\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Like\app\Models\Like;
use Modules\Post\app\Models\Post;

class LikeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Like::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'user_id' => rand(1, 1000),
            'post_id' => Post::inRandomOrder()->first() ?? Post::factory()->create(),
        ];
    }
}
