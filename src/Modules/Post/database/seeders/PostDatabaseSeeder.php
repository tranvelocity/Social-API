<?php

namespace Modules\Post\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Post\app\Models\Post;

class PostDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory()->count(100)->create();
    }
}
