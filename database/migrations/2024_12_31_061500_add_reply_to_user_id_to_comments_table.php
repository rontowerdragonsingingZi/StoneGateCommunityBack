<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 添加 reply_to_user_id 字段支持抖音式扁平回复
     * 所有回复都在主评论下平级显示，通过 reply_to_user_id 记录回复给谁
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('reply_to_user_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('回复给哪个用户');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['reply_to_user_id']);
            $table->dropColumn('reply_to_user_id');
        });
    }
};
