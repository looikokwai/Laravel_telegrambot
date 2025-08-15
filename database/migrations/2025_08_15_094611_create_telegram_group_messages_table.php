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
        Schema::create('telegram_group_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('telegram_groups')->onDelete('cascade')->comment('关联群组');
            $table->string('message_id')->nullable()->comment('Telegram 消息 ID');
            $table->text('message_text')->comment('消息内容');
            $table->string('image_path')->nullable()->comment('图片路径');
            $table->json('keyboard')->nullable()->comment('键盘配置');
            $table->enum('sent_by', ['bot', 'admin'])->default('admin')->comment('发送者');
            $table->enum('status', ['sent', 'failed'])->default('sent')->comment('发送状态');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();

            // 添加索引
            $table->index('group_id');
            $table->index('message_id');
            $table->index('sent_by');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_group_messages');
    }
};
