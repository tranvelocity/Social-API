<?php

namespace Modules\User\database\seeders;

use Illuminate\Database\Seeder;
use Modules\User\app\Models\User;

class UserDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(50)->create();
    }
}
