<?php

namespace Modules\Poster\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Poster\app\Models\Poster;

class PosterDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Poster::factory()->count(10)->create();
    }
}
