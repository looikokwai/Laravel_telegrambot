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
            $table->dropColumn('selection_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_languages', function (Blueprint $table) {
            $table->text('selection_image')->nullable()->after('selection_prompt');
        });
    }
};
