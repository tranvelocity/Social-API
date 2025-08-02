<?php

namespace Modules\RestrictedUser\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\RestrictedUser\app\Models\RestrictedUser;

class RestrictedUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = RestrictedUser::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'admin_uuid' => $this->faker->uuid(),
            'user_id' => $this->faker->numberBetween(1, 100000),
            'remarks' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
