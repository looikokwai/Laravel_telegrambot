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
        Schema::create('telegram_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_id')->unique()->comment('Telegram 群组 ID');
            $table->string('title')->comment('群组名称');
            $table->text('description')->nullable()->comment('群组描述');
            $table->enum('type', ['group', 'supergroup'])->comment('群组类型');
            $table->string('username')->nullable()->comment('群组用户名');
            $table->integer('member_count')->default(0)->comment('成员数量');
            $table->boolean('is_active')->default(true)->comment('是否活跃');
            $table->json('permissions')->nullable()->comment('Bot 权限信息');
            $table->timestamp('last_activity')->nullable()->comment('最后活跃时间');
            $table->timestamps();

            // 添加索引
            $table->index(['group_id', 'is_active']);
            $table->index('type');
            $table->index('username');
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_groups');
    }
};
