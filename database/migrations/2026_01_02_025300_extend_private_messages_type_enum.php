<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 扩展 private_messages 的 type 枚举，支持更多消息类型
     */
    public function up(): void
    {
        // 修改 enum 类型，添加新的消息类型
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'sticker', 'post_share', 'system') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 还原为原来的 enum
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'system') DEFAULT 'text'");
    }
};
