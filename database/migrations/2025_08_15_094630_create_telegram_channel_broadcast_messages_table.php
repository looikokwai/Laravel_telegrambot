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
        Schema::create('telegram_channel_broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message')->comment('消息内容');
            $table->json('target_channels')->nullable()->comment('目标频道 ID 数组');
            $table->enum('target_type', ['all', 'selected', 'active'])->default('selected')->comment('目标类型');
            $table->string('image_path')->nullable()->comment('图片路径');
            $table->json('keyboard')->nullable()->comment('键盘配置');
            $table->integer('total_channels')->default(0)->comment('目标频道总数');
            $table->integer('sent_count')->default(0)->comment('发送成功数量');
            $table->integer('failed_count')->default(0)->comment('发送失败数量');
            $table->enum('status', ['pending', 'completed', 'completed_with_errors', 'failed'])->default('pending')->comment('发送状态');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();

            // 添加索引
            $table->index('target_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_channel_broadcast_messages');
    }
};
