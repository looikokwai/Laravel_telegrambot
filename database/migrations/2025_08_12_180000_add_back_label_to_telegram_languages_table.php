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
        Schema::table('telegram_languages', function (Blueprint $table) {
            // 返回按钮文案（多语言可配置）。为空时使用默认“🔙 返回”。
            $table->string('back_label', 50)->nullable()->after('selection_prompt')->comment('返回按钮文案');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_languages', function (Blueprint $table) {
            $table->dropColumn('back_label');
        });
    }
};


