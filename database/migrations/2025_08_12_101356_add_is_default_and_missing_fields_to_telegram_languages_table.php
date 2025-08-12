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
            $table->boolean('is_default')->default(false)->after('is_active')->comment('是否为默认语言');
            $table->string('flag_emoji', 10)->nullable()->after('native_name')->comment('国旗表情符号');
            $table->boolean('is_rtl')->default(false)->after('flag_emoji')->comment('是否为从右到左的语言');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_languages', function (Blueprint $table) {
            $table->dropColumn(['is_default', 'flag_emoji', 'is_rtl']);
        });
    }
};
