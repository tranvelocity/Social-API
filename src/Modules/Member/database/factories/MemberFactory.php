<?php

namespace Modules\Member\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Member\app\Models\Member;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition()
    {
        return [
            'user_id' => $this->faker->numberBetween(1, 100),
            'membership_number' => $this->faker->unique()->bothify('MBR####'),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'joined_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'expired_at' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }
}
