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
        Schema::create('telegram_menu_images', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255)->comment('文件名');
            $table->string('original_name', 255)->comment('原始文件名');
            $table->string('path', 500)->comment('文件路径');
            $table->string('url', 500)->comment('访问URL');
            $table->string('mime_type', 100)->comment('MIME类型');
            $table->unsignedBigInteger('file_size')->comment('文件大小(字节)');
            $table->unsignedInteger('width')->nullable()->comment('图片宽度');
            $table->unsignedInteger('height')->nullable()->comment('图片高度');
            $table->string('alt_text', 255)->nullable()->comment('替代文本');
            $table->json('metadata')->nullable()->comment('扩展数据');
            $table->timestamps();
            
            $table->index(['mime_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_menu_images');
    }
};
