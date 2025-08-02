<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->string('id', 36)->unique()->index()->primary();
            $table->unsignedInteger('user_id')->index('comments__user_id__index');
            $table->text('comment')->nullable();
            $table->tinyInteger('is_hidden')->default(0);

            // Foreign key
            $table->foreignUlid('post_id')
                ->constrained()
                ->references('id')
                ->on('posts')
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
        Schema::dropIfExists('comments');
    }
};
