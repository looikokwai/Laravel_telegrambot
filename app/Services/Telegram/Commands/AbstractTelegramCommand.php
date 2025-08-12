<?php

namespace App\Services\Telegram\Commands;

use App\Models\TelegramUser;
use App\Services\TelegramLanguageService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

/**
 * Telegram命令处理器抽象基类
 */
abstract class AbstractTelegramCommand implements TelegramCommandInterface
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 无需依赖注入，使用静态方法
    }

    /**
     * 获取用户的翻译文本
     *
     * @param TelegramUser $user
     * @param string $key
     * @param array $replace
     * @return string
     */
    protected function trans(TelegramUser $user, string $key, array $replace = []): string
    {
        return TelegramLanguageService::transForUser($user->telegram_user_id, $key, $replace);
    }

    /**
     * 发送文本消息
     *
     * @param TelegramUser $user
     * @param string $text
     * @param array $keyboard
     * @return bool
     */
    protected function sendMessage(TelegramUser $user, string $text, array $keyboard = []): bool
    {
        try {
            // 将字面的\n字符串转换为实际的换行符
            $formattedText = str_replace('\\n', "\n", $text);
            
            $params = [
                'chat_id' => $user->chat_id,
                'text' => $formattedText,
                'parse_mode' => 'HTML'
            ];
            
            if (!empty($keyboard)) {
                $params['reply_markup'] = json_encode($keyboard);
            }
            
            Telegram::sendMessage($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'telegram_user_id' => $user->telegram_user_id,
                'text' => $text
            ]);
            return false;
        }
    }

    /**
     * 编辑消息
     *
     * @param TelegramUser $user
     * @param int $messageId
     * @param string $text
     * @param array $keyboard
     * @return bool
     */
    protected function editMessage(TelegramUser $user, int $messageId, string $text, array $keyboard = []): bool
    {
        try {
            // 将字面的\n字符串转换为实际的换行符
            $formattedText = str_replace('\\n', "\n", $text);
            
            $params = [
                'chat_id' => $user->chat_id,
                'message_id' => $messageId,
                'text' => $formattedText,
                'parse_mode' => 'HTML'
            ];
            
            if (!empty($keyboard)) {
                $params['reply_markup'] = json_encode($keyboard);
            }
            
            Telegram::editMessageText($params);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to edit Telegram message: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'telegram_user_id' => $user->telegram_user_id,
                'message_id' => $messageId,
                'text' => $text
            ]);
            return false;
        }
    }

    /**
     * 创建内联键盘
     *
     * @param array $buttons
     * @return array
     */
    protected function createInlineKeyboard(array $buttons): array
    {
        return [
            'inline_keyboard' => $buttons
        ];
    }
}