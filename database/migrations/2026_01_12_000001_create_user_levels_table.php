<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 用户等级配置表：存储等级定义、称号、图标等
     */
    public function up(): void
    {
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();       // 等级数字 (0-255)
            $table->string('title', 50);                          // 称号名称
            $table->string('code', 50)->nullable();               // 称号代码 (e.g., Observer-β00)
            $table->string('icon', 100)->nullable();              // 称号图标 (可以是 emoji 或图片路径)
            $table->string('quote', 200)->nullable();             // 引语/格言
            $table->text('description')->nullable();              // 详细描述
            $table->unsignedInteger('min_experience')->default(0); // 该等级所需最低经验值
            $table->string('color', 20)->nullable();              // 称号颜色 (用于前端展示)
            $table->timestamps();

            // 索引：按经验值查询
            $table->index('min_experience');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
