<?php

/**
 * 清理脚本：删除错误保存的Bot用户记录
 * 
 * 这个脚本用于删除telegram_users表中错误保存的Bot自己的用户信息
 * Bot的用户ID: 7201604245
 * Bot的用户名: surewin_official_bot
 */

require_once __DIR__ . '/vendor/autoload.php';

// 启动Laravel应用
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

echo "开始清理Bot用户记录...\n";

// Bot的用户ID
$botUserId = '7201604245';
$botUsername = 'surewin_official_bot';

try {
    // 查找Bot用户记录
    $botUsers = TelegramUser::where('telegram_user_id', $botUserId)
                           ->orWhere('username', $botUsername)
                           ->get();
    
    if ($botUsers->isEmpty()) {
        echo "✅ 没有找到需要清理的Bot用户记录\n";
    } else {
        echo "找到 {$botUsers->count()} 条Bot用户记录：\n";
        
        foreach ($botUsers as $botUser) {
            echo "- ID: {$botUser->id}, Telegram ID: {$botUser->telegram_user_id}, Username: {$botUser->username}, Name: {$botUser->first_name}\n";
        }
        
        echo "\n确认删除这些记录吗？(y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) === 'y') {
            $deletedCount = TelegramUser::where('telegram_user_id', $botUserId)
                                      ->orWhere('username', $botUsername)
                                      ->delete();
            
            echo "✅ 成功删除 {$deletedCount} 条Bot用户记录\n";
            
            Log::info('Bot user records cleaned up', [
                'deleted_count' => $deletedCount,
                'bot_user_id' => $botUserId,
                'bot_username' => $botUsername
            ]);
        } else {
            echo "❌ 取消删除操作\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 清理过程中发生错误: " . $e->getMessage() . "\n";
    Log::error('Bot user cleanup failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "清理脚本执行完成\n";