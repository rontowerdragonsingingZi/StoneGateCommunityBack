<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 通知表 - 支持点赞、评论、转发、私聊、好友请求等通知
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // 接收通知的用户
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // 触发通知的用户
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            // 通知类型：post_like, comment_like, comment, comment_reply, post_share, private_message, friend_request
            $table->string('type', 50);
            
            // 多态关联 - 关联的对象（帖子/评论/私聊消息/好友请求等）
            $table->nullableMorphs('notifiable');
            
            // 额外数据（JSON格式，存储如帖子标题、评论内容预览等）
            $table->json('data')->nullable();
            
            // 是否已读
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            
            // 索引优化查询
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
