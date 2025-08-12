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
            $table->string('selection_title')->nullable()->after('sort_order');
            $table->text('selection_prompt')->nullable()->after('selection_title');
            $table->text('selection_image')->nullable()->after('selection_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_languages', function (Blueprint $table) {
            $table->dropColumn(['selection_title', 'selection_prompt', 'selection_image']);
        });
    }
};
