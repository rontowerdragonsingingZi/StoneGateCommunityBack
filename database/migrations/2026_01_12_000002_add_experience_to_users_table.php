<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 为用户表添加经验值和等级字段
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('experience')->default(0)->after('contact'); // 经验值
            $table->unsignedTinyInteger('level')->default(0)->after('experience'); // 当前等级

            // 索引：用于排行榜等查询
            $table->index('experience');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['experience']);
            $table->dropIndex(['level']);
            $table->dropColumn(['experience', 'level']);
        });
    }
};
