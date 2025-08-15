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
            if ($update->has('message') && $update->getMessage()) {
                $this->handleMessage($update->getMessage());
            }

            // 处理回调查询（内联键盘点击）
            if ($update->has('callback_query') && $update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
            }

            // 处理 bot 成员状态变更（被踢出、权限变更等）
            if ($update->has('my_chat_member') && $update->getMyChatMember()) {
                $this->handleMyChatMember($update->getMyChatMember());
            }

            // 处理频道消息
            if ($update->has('channel_post') && $update->getChannelPost()) {
                $this->handleChannelPost($update->getChannelPost());
            }

            // 处理编辑的消息
            if ($update->has('edited_message') && $update->getEditedMessage()) {
                $this->handleEditedMessage($update->getEditedMessage());
            }

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

    /**
     * 处理 bot 成员状态变更
     */
    private function handleMyChatMember($chatMember): void
    {
        $chatId = $chatMember->getChat()->getId();
        $userId = $chatMember->getFrom()->getId();
        $oldStatus = $chatMember->getOldChatMember()->getStatus();
        $newStatus = $chatMember->getNewChatMember()->getStatus();

        // 如果 bot 被踢出，可以在这里处理相关逻辑
        if ($newStatus === 'kicked') {
            // Bot 被踢出处理逻辑
        }
    }

    /**
     * 处理频道消息
     */
    private function handleChannelPost($channelPost): void
    {
        $chatId = $channelPost->getChat()->getId();
        $text = $channelPost->getText();

        // 这里可以添加处理频道消息的逻辑
    }

    /**
     * 处理编辑的消息
     */
    private function handleEditedMessage($editedMessage): void
    {
        $chatId = $editedMessage->getChat()->getId();
        $userId = $editedMessage->getFrom()->getId();
        $text = $editedMessage->getText();

        // 这里可以添加处理编辑消息的逻辑
    }
}
