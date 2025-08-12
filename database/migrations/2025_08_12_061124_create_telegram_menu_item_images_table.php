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
        Schema::create('telegram_menu_item_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id')->comment('菜单项ID');
            $table->unsignedBigInteger('image_id')->comment('图片ID');
            $table->unsignedBigInteger('language_id')->nullable()->comment('语言ID，为空表示通用');
            $table->enum('type', ['icon', 'banner', 'thumbnail'])->default('icon')->comment('图片类型');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->foreign('menu_item_id')->references('id')->on('telegram_menu_items')->onDelete('cascade');
            $table->foreign('image_id')->references('id')->on('telegram_menu_images')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('telegram_languages')->onDelete('cascade');
            $table->unique(['menu_item_id', 'image_id', 'language_id', 'type'], 'unique_menu_item_image');
            $table->index(['menu_item_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_menu_item_images');
    }
};
