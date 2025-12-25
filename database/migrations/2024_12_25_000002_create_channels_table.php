<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique(); // 频道代号，用于 WebSocket
            $table->string('display_name', 128);   // 显示名称
            $table->string('description', 500)->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_default')->default(false); // 默认频道
            $table->boolean('is_private')->default(false); // 私有频道（扩展用）
            $table->timestamps();
        });

        // 插入默认大厅频道
        DB::table('channels')->insert([
            'name' => 'lobby',
            'display_name' => 'ROUND_TABLE_CONFERENCE',
            'description' => '未来道具研究所圆桌会议大厅',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
