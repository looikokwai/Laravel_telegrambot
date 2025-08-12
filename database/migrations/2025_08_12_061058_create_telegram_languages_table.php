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
        Schema::create('telegram_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('语言代码，如 zh, en, es');
            $table->string('name', 100)->comment('语言名称，如 中文, English, Español');
            $table->string('native_name', 100)->comment('本地语言名称');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_languages');
    }
};
