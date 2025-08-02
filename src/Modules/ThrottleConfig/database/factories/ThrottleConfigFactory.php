<?php

namespace Modules\ThrottleConfig\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

class ThrottleConfigFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ThrottleConfig::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'admin_uuid' => $this->faker->uuid(),
            'time_frame_minutes' => $this->faker->numberBetween(1, 1440),
            'max_comments' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
