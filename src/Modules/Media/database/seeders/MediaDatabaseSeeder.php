<?php

namespace Modules\Media\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Media\app\Models\Media;

class MediaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Media::factory()->count(100)->create();
    }
}
