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
        Schema::create('telegram_language_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('language_id')->comment('语言ID');
            $table->unsignedBigInteger('image_id')->comment('图片ID');
            $table->enum('type', ['selection'])->default('selection')->comment('图片类型');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->foreign('language_id')->references('id')->on('telegram_languages')->onDelete('cascade');
            $table->foreign('image_id')->references('id')->on('telegram_menu_images')->onDelete('cascade');
            $table->unique(['language_id', 'image_id', 'type'], 'unique_language_image');
            $table->index(['language_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_language_images');
    }
};
