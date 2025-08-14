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
        Schema::create('broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message');                    // 消息内容
            $table->string('target');                   // 目标用户类型 (all, active, recent, recent_30)
            $table->string('image_path')->nullable();   // 图片路径
            $table->json('keyboard')->nullable();       // 键盘配置
            $table->integer('total_users');             // 目标用户总数
            $table->integer('sent_count')->default(0);  // 成功发送数量
            $table->integer('failed_count')->default(0); // 失败发送数量
            $table->string('status')->default('pending'); // 状态 (pending, completed, completed_with_errors, failed)
            $table->timestamp('sent_at')->nullable();   // 发送完成时间
            $table->timestamps();

            // 索引
            $table->index(['target', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_messages');
    }
};
