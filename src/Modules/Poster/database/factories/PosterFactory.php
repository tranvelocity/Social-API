<?php

namespace Modules\Poster\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Poster\app\Models\Poster;

class PosterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Poster::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'admin_uuid' => Str::uuid(),
            'user_id' => $this->faker->randomNumber(5, false),
            'description' => $this->faker->paragraph,
        ];
    }
}
