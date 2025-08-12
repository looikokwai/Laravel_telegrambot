<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 修改action字段的枚举值，添加submenu_shown
        DB::statement("ALTER TABLE telegram_menu_stats MODIFY COLUMN action ENUM('click', 'view', 'share', 'start_menu_shown', 'submenu_shown') COMMENT '操作类型'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚到原来的枚举值
        DB::statement("ALTER TABLE telegram_menu_stats MODIFY COLUMN action ENUM('click', 'view', 'share', 'start_menu_shown') COMMENT '操作类型'");
    }
};