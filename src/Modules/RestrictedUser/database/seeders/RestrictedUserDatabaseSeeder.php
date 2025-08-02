<?php

namespace Modules\RestrictedUser\database\seeders;

use Illuminate\Database\Seeder;
use Modules\RestrictedUser\app\Models\RestrictedUser;

class RestrictedUserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RestrictedUser::factory()->count(100)->create();
    }
}
