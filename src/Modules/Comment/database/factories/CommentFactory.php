<?php

namespace Modules\Comment\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Comment\app\Models\Comment;
use Modules\Post\app\Models\Post;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'user_id' => $this->faker->randomNumber(5, false),
            'post_id' => Post::inRandomOrder()->first() ?? Post::factory()->create(),
            'comment' => $this->faker->paragraph,
            'is_hidden' => $this->faker->boolean,
        ];
    }
}
