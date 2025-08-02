<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('email', 255)->unique()->default('');
            $table->string('last_name', 128)->default('');
            $table->string('first_name', 128)->default('');
            $table->string('first_name_kana', 128)->nullable();
            $table->string('last_name_kana', 128)->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('post_code', 20)->nullable();
            $table->string('prefecture', 128)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('gender', 16)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
