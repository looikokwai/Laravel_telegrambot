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
        Schema::create('telegram_menu_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id')->nullable()->comment('菜单项ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->enum('action', ['click', 'view', 'share', 'start_menu_shown'])->comment('操作类型');
            $table->string('session_id', 100)->nullable()->comment('会话ID');
            $table->json('metadata')->nullable()->comment('扩展数据');
            $table->timestamp('action_time')->comment('操作时间');
            $table->timestamps();
            
            $table->foreign('menu_item_id')->references('id')->on('telegram_menu_items')->onDelete('cascade')->nullable();
            $table->foreign('user_id')->references('id')->on('telegram_users')->onDelete('cascade');
            $table->index(['menu_item_id', 'action', 'action_time']);
            $table->index(['user_id', 'action_time']);
            $table->index(['action_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_menu_stats');
    }
};
