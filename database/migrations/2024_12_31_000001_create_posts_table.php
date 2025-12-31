<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 200);
            $table->text('content');
            $table->string('cover')->nullable();          // 封面图片
            $table->string('tag', 50)->default('GENERAL'); // 标签: THEORY, TECH, MISSION, GENERAL
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);    // 冗余计数，提升查询性能
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['created_at']);
            $table->index(['tag']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
