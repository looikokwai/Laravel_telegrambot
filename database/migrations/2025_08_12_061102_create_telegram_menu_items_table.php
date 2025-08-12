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
        Schema::create('telegram_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('菜单项唯一标识');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父级菜单ID');
            $table->enum('type', ['button', 'submenu', 'url', 'callback'])->comment('菜单类型');
            $table->string('callback_data', 255)->nullable()->comment('回调数据');
            $table->string('url', 500)->nullable()->comment('链接地址');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->json('metadata')->nullable()->comment('扩展数据');
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('telegram_menu_items')->onDelete('cascade');
            $table->index(['parent_id', 'is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_menu_items');
    }
};
