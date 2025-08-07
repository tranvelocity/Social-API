<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->char('uuid', 36)->primary();
            $table->char('admin_uuid', 36)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->bigInteger('member_number')->unique();
            $table->dateTime('resigned_at')->nullable();
            $table->tinyInteger('is_resigned')->default(0);
            $table->string('nickname', 128)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unique('uuid', 'members_uuid_unique');
            $table->unique(['user_id', 'admin_uuid'], 'members_unique_idx1');
            $table->index(['admin_uuid', 'deleted_at', 'created_at'], 'members_composite_idx');
            $table->index(['admin_uuid', 'created_at'], 'members_composite_idx2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
