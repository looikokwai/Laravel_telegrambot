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
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->unique()->comment('Telegram 频道 ID');
            $table->string('title')->comment('频道名称');
            $table->text('description')->nullable()->comment('频道描述');
            $table->enum('type', ['channel'])->default('channel')->comment('频道类型');
            $table->string('username')->nullable()->comment('频道用户名');
            $table->integer('subscriber_count')->default(0)->comment('订阅者数量');
            $table->boolean('is_public')->default(true)->comment('是否公开');
            $table->boolean('is_active')->default(true)->comment('是否活跃');
            $table->json('permissions')->nullable()->comment('Bot 权限信息');
            $table->timestamp('last_activity')->nullable()->comment('最后活跃时间');
            $table->timestamps();

            // 添加索引
            $table->index(['channel_id', 'is_active']);
            $table->index('type');
            $table->index('username');
            $table->index('is_public');
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
    }
};
