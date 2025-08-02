<?php

namespace Modules\ThrottleConfig\database\seeders;

use Illuminate\Database\Seeder;
use Modules\ThrottleConfig\app\Models\ThrottleConfig;

class ThrottleConfigDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ThrottleConfig::factory()->count(10)->create();
    }
}
