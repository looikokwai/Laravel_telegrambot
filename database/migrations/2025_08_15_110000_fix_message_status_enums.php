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
        // 修复群组消息表的 status 字段
        DB::statement("ALTER TABLE telegram_group_messages MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'completed', 'completed_with_errors') DEFAULT 'pending'");

        // 修复频道消息表的 status 字段
        DB::statement("ALTER TABLE telegram_channel_messages MODIFY COLUMN status ENUM('pending', 'sent', 'failed', 'completed', 'completed_with_errors') DEFAULT 'pending'");

        // 修复群组广播消息表的 status 字段
        DB::statement("ALTER TABLE telegram_group_broadcast_messages MODIFY COLUMN status ENUM('pending', 'completed', 'completed_with_errors', 'failed') DEFAULT 'pending'");

        // 修复频道广播消息表的 status 字段
        DB::statement("ALTER TABLE telegram_channel_broadcast_messages MODIFY COLUMN status ENUM('pending', 'completed', 'completed_with_errors', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复群组消息表的 status 字段
        DB::statement("ALTER TABLE telegram_group_messages MODIFY COLUMN status ENUM('sent', 'failed') DEFAULT 'sent'");

        // 恢复频道消息表的 status 字段
        DB::statement("ALTER TABLE telegram_channel_messages MODIFY COLUMN status ENUM('sent', 'failed') DEFAULT 'sent'");

        // 恢复群组广播消息表的 status 字段
        DB::statement("ALTER TABLE telegram_group_broadcast_messages MODIFY COLUMN status ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");

        // 恢复频道广播消息表的 status 字段
        DB::statement("ALTER TABLE telegram_channel_broadcast_messages MODIFY COLUMN status ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
    }
};
