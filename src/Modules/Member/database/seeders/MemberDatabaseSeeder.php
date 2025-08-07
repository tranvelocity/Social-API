<?php

namespace Modules\Member\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Member\app\Models\Member;

class MemberDatabaseSeeder extends Seeder
{
    public function run()
    {
        Member::factory()->count(10)->create();
    }
}
