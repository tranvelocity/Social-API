<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Media\app\Models\Media;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medias', function (Blueprint $table) {
            $table->string('id', 36)->unique()->index()->primary();
            $table->string('path', 256);
            $table->string('thumbnail', 256)->nullable();
            $table->string('post_id', 36)->nullable()->index('medias__post_id__index');
            $table->enum('type', [Media::VIDEO_TYPE, Media::IMAGE_TYPE])->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medias');
    }
};
