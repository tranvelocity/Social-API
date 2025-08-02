<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Post\app\Models\Post;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->string('id', 36)->unique()->index()->primary();
            $table->uuid('admin_uuid')->index('posts__admin_uuid__index');
            $table->longText('content')->nullable();
            $table->tinyInteger('is_published')->default(0);
            $table->enum('type', [Post::FREE_TYPE, Post::PREMIUM_TYPE])->default('free');
            $table->dateTime('published_start_at')->nullable();
            $table->dateTime('published_end_at')->nullable();

            $table->foreignUlid('poster_id')
                ->constrained()
                ->references('id')
                ->on('posters')
                ->onDelete('NO ACTION')
                ->onUpdate('NO ACTION');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
