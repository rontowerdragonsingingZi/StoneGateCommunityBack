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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel', 64)->default('lobby')->index(); // 频道名：lobby, room.1, private.1.2
            $table->text('content');
            $table->enum('type', ['text', 'image', 'system'])->default('text');
            $table->timestamps();

            // 索引：按频道和时间查询
            $table->index(['channel', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
