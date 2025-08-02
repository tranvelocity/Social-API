<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restricted_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('admin_uuid', 36)->index('restricted_users__admin_uuid__index');
            $table->unsignedInteger('user_id')->index('restricted_users__user_id__index');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restricted_users');
    }
};
