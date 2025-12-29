<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 创建表情相关表
     */
    public function up(): void
    {
        // 表情表
        Schema::create('stickers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('上传者ID，null表示系统表情');
            $table->string('name', 100)->comment('表情名称');
            $table->string('url', 500)->comment('表情图片URL');
            $table->string('category', 50)->default('default')->comment('分类');
            $table->boolean('is_public')->default(true)->comment('是否公开（其他用户可见）');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'category']);
            $table->index('is_public');
        });

        // 用户表情收藏表
        Schema::create('user_sticker_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('收藏者ID');
            $table->unsignedBigInteger('sticker_id')->comment('表情ID');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sticker_id')->references('id')->on('stickers')->onDelete('cascade');
            $table->unique(['user_id', 'sticker_id']);
        });

        // 添加 sticker 类型到消息表
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'sticker', 'system') DEFAULT 'text'");
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'sticker', 'system') DEFAULT 'text'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sticker_collections');
        Schema::dropIfExists('stickers');

        // 回滚消息类型
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') DEFAULT 'text'");
        DB::statement("ALTER TABLE private_messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') DEFAULT 'text'");
    }
};
