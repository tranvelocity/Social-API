<?php

namespace Modules\Admin\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Admin\app\Models\Admin;

class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'app_code' => $this->faker->unique()->word,
            'app_name' => $this->faker->company,
            'api_key' => Str::random(32),
            'api_secret' => Str::random(64),
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'description' => $this->faker->paragraph,
        ];
    }
}
