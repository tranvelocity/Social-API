<?php

namespace Modules\Like\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Like\app\Models\Like;

class LikeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Like::factory()->count(2000)->create();
    }
}
