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
        Schema::create('telegram_menu_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id')->comment('菜单项ID');
            $table->unsignedBigInteger('language_id')->comment('语言ID');
            $table->string('title', 255)->comment('菜单标题');
            $table->text('description')->nullable()->comment('菜单描述');
            $table->timestamps();
            
            $table->foreign('menu_item_id')->references('id')->on('telegram_menu_items')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('telegram_languages')->onDelete('cascade');
            $table->unique(['menu_item_id', 'language_id']);
            $table->index(['language_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_menu_translations');
    }
};
