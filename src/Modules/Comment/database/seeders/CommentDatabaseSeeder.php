<?php

namespace Modules\Comment\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Comment\app\Models\Comment;

class CommentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Comment::factory()->count(1000)->create();
    }
}
