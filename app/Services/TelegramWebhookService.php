<?php

namespace App\Services;

use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class TelegramWebhookService
{
    public function __construct(
        private TelegramUserService $userService,
        private TelegramMessageService $messageService
    ) {}

    /**
     * 处理Telegram webhook更新
     */
    public function handleUpdate(Update $update): void
    {
        try {
            // 处理普通消息
            if ($update->getMessage()) {
                $this->handleMessage($update->getMessage());
            }

            // 处理回调查询（内联键盘点击）
            if ($update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
            }

            // 处理其他类型的更新...
        } catch (\Exception $e) {
            Log::error('Telegram webhook processing error: ' . $e->getMessage(), [
                'update_id' => $update->getUpdateId(),
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * 处理普通消息
     */
    private function handleMessage($message): void
    {
        $chatId = $message->getChat()->getId();
        $userId = $message->getFrom()->getId();
        $username = $message->getFrom()->getUsername();
        $firstName = $message->getFrom()->getFirstName();
        $lastName = $message->getFrom()->getLastName();
        $text = $message->getText();
        $languageCode = $message->getFrom()->getLanguageCode();
        $isBot = $message->getFrom()->getIsBot();

        // 过滤掉Bot自己的消息，避免将Bot信息保存到数据库
        if ($isBot) {
            Log::info('Ignoring message from bot', [
                'bot_id' => $userId,
                'bot_username' => $username
            ]);
            return;
        }

        // 保存或更新用户信息
        $telegramUser = $this->userService->saveOrUpdateUser(
            $userId,
            $chatId,
            $username,
            $firstName,
            $lastName,
            $languageCode
        );

        // 处理用户消息
        $this->messageService->handleUserMessage($telegramUser, $text);
    }

    /**
     * 处理回调查询（内联键盘点击）
     */
    private function handleCallbackQuery($callbackQuery): void
    {
        $data = $callbackQuery->getData();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $userId = $callbackQuery->getFrom()->getId();
        $messageId = $callbackQuery->getMessage()->getMessageId();
        $callbackQueryId = $callbackQuery->getId();

        $telegramUser = $this->userService->findByTelegramId($userId);
        
        if ($telegramUser) {
            $this->messageService->handleCallbackQuery($telegramUser, $data, $messageId, $callbackQueryId);
        }
    }
}