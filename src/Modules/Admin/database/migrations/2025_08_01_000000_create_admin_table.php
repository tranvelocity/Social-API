<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('app_code', 128)->unique();
            $table->string('app_name', 255);
            $table->longText('description')->nullable();
            $table->string('api_key', 64)->unique();
            $table->string('api_secret', 64)->unique();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
