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
        Schema::table('throttle_configs', function (Blueprint $table) {
            $table->renameColumn('time_frame_hours', 'time_frame_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('throttle_configs', function (Blueprint $table) {
            $table->renameColumn('time_frame_minutes', 'time_frame_hours');
        });
    }
};
