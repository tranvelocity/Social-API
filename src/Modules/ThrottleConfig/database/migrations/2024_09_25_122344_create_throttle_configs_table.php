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
        Schema::create('throttle_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('admin_uuid', 36)->index('throttle_configs__admin_uuid__index');
            $table->unsignedInteger('time_frame_hours');
            $table->unsignedInteger('max_comments');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('throttle_configs');
    }
};
