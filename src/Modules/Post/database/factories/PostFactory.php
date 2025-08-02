<?php

namespace Modules\Post\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Post\app\Models\Post;
use Modules\Poster\app\Models\Poster;

class PostFactory extends Factory
{
    protected string $table = 'posts';

    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'admin_uuid' => Str::uuid(),
            'poster_id' => Poster::inRandomOrder()->first() ?? Poster::factory()->create(),
            'content' => $this->faker->text,
            'is_published' => $this->faker->boolean,
            'type' => $this->faker->randomElement([Post::FREE_TYPE, Post::PREMIUM_TYPE]),
            'published_start_at' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'published_end_at' => $this->faker->dateTimeBetween('+1 years', '+3 years'),
            'created_at' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-3 years', 'now'),
        ];
    }
}
