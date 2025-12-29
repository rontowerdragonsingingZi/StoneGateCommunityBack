<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 创建表情包表，为后续表情包市场扩展预留
     */
    public function up(): void
    {
        // 表情包表
        Schema::create('sticker_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('表情包名称');
            $table->string('description', 500)->nullable()->comment('表情包描述');
            $table->string('cover_url', 500)->nullable()->comment('封面图URL');
            $table->unsignedBigInteger('author_id')->nullable()->comment('作者ID，null表示官方');
            $table->boolean('is_official')->default(false)->comment('是否官方表情包');
            $table->unsignedInteger('download_count')->default(0)->comment('下载次数');
            $table->decimal('price', 10, 2)->default(0)->comment('价格，0表示免费');
            $table->boolean('is_active')->default(true)->comment('是否上架');
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->index('is_official');
            $table->index('is_active');
            $table->index('download_count');
        });

        // 为 stickers 表添加 pack_id 字段
        Schema::table('stickers', function (Blueprint $table) {
            $table->unsignedBigInteger('pack_id')->nullable()->after('category')->comment('所属表情包ID');
            $table->foreign('pack_id')->references('id')->on('sticker_packs')->onDelete('set null');
            $table->index('pack_id');
        });

        // 用户已下载的表情包
        Schema::create('user_sticker_packs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('pack_id')->comment('表情包ID');
            $table->timestamp('downloaded_at')->useCurrent()->comment('下载时间');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('pack_id')->references('id')->on('sticker_packs')->onDelete('cascade');
            $table->unique(['user_id', 'pack_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sticker_packs');
        
        Schema::table('stickers', function (Blueprint $table) {
            $table->dropForeign(['pack_id']);
            $table->dropIndex(['pack_id']);
            $table->dropColumn('pack_id');
        });

        Schema::dropIfExists('sticker_packs');
    }
};
