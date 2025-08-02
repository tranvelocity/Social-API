<?php

namespace Modules\User\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\User\app\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => Str::ulid()->toBase32(),
            'email' => $this->faker->unique()->safeEmail,
            'last_name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'first_name_kana' => $this->faker->firstName,
            'last_name_kana' => $this->faker->lastName,
            'tel' => $this->faker->phoneNumber,
            'post_code' => $this->faker->postcode,
            'prefecture' => $this->faker->prefecture,
            'address' => $this->faker->address,
            'password' => bcrypt('password'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
