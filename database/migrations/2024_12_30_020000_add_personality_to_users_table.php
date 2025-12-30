<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 为用户表添加人设字段（主要用于机器人）
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('personality')->nullable()->after('is_bot')->comment('人设描述（用于AI机器人）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('personality');
        });
    }
};
