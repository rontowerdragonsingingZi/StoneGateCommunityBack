<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 添加 file 类型到 messages 和 private_messages 表
     */
    public function up(): void
    {
        // 修改 messages 表的 type 字段
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') DEFAULT 'text'");

        // 修改 private_messages 表的 type 字段
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚时移除 file 类型（注意：如果有 file 类型的数据会失败）
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'system') DEFAULT 'text'");
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'system') DEFAULT 'text'");
    }
};