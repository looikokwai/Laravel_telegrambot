<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\TelegramMenuItem;
use App\Models\TelegramUserMessage;
use App\Jobs\SendTelegramMessage;
use App\Services\Telegram\Commands\TelegramCommandFactory;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\CallbackRouter;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramMessageService
{
    /**
     * /start 默认进入的根菜单 key
     */
    private const DEFAULT_START_ROOT_KEY = 'welcome_message';
    private TelegramLanguageService $languageService;
    private TelegramMenuService $menuService;
    private StartCommand $startCommand;

    public function __construct(
        TelegramLanguageService $languageService,
        TelegramMenuService $menuService,
        StartCommand $startCommand
    ) {
        $this->languageService = $languageService;
        $this->menuService = $menuService;
        $this->startCommand = $startCommand;
    }

    /**
     * 发送菜单项信息（标题 + 描述）
     */
    public function sendMenuItemInfo(TelegramUser $user, TelegramMenuItem $menuItem, ?int $languageId = null): void
    {
        // 读取翻译
        $translation = $menuItem->getTranslation($languageId);
        $title = $translation ? ($translation->title ?? $menuItem->key) : $menuItem->key;
        $desc = $translation ? ($translation->description ?? null) : null;

        $text = $desc
            ? ("<b>" . e($title) . "</b>\n\n" . e($desc))
            : e($title);

        // 优先发送图片 + caption
        try {
            $imageRelation = $menuItem->getImage($languageId, 'detail')
                ?? $menuItem->getImage($languageId, 'icon')
                ?? $menuItem->getImage(null, 'detail')
                ?? $menuItem->getImage(null, 'icon');

            if ($imageRelation && $imageRelation->image) {
                $image = $imageRelation->image;
                $storagePath = storage_path('app/private/' . $image->path);
                if (file_exists($storagePath)) {
                    $inputFile = \Telegram\Bot\FileUpload\InputFile::create($storagePath, $image->filename);
                    Telegram::sendPhoto([
                        'chat_id' => $user->chat_id,
                        'photo' => $inputFile,
                        'caption' => $text,
                        'parse_mode' => 'HTML',
                    ]);
                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning('发送菜单项图片失败：' . $e->getMessage());
        }

        // 回退为纯文本
        $this->sendMessage($user->chat_id, $text);
    }
    /**
     * 处理用户消息
     */
    public function handleUserMessage(TelegramUser $user, ?string $text): void
    {
        if (!$text) {
            return;
        }

        // 检查用户是否已选择语言
        if (!$user->language_selected) {
            $this->sendLanguageSelection($user->chat_id);
            return;
        }

        // 处理命令
        $command = strtolower(trim($text));

        // 检查是否为命令（以/开头）
        if (str_starts_with($command, '/')) {
            // 特判 /start：直接定向到 welcome_message 子菜单
            if (preg_match('/^\/start(\@\w+)?$/', $command)) {
                try {
                    $welcomeMenu = $this->menuService->findMenuItemByKey(self::DEFAULT_START_ROOT_KEY);
                    if ($welcomeMenu) {
                                            $this->startCommand->execute($user, ['text' => $text], $welcomeMenu->id);
                    return;
                }
                // 找不到则继续走原有命令处理逻辑（显示根级）
                } catch (\Exception $e) {
                    Log::error('处理 /start 定向失败：' . $e->getMessage());
                    // 出错也回退到原有命令处理逻辑
                }
            }

            $commandFactory = new TelegramCommandFactory();
            $commandHandler = $commandFactory->getCommandHandler($command);

            if ($commandHandler) {
                try {
                    $message = ['text' => $text];
                    $commandHandler->execute($user, $message);
                    return;
                } catch (\Exception $e) {
                    Log::error("Command execution failed: {$command}", [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 如果不是命令或命令执行失败，发送默认回复
        $this->sendDefaultResponse($user, $text);
    }

    /**
     * 处理回调查询
     */
    public function handleCallbackQuery(TelegramUser $user, string $data, int $messageId, string $callbackQueryId): void
    {
        try {
            // 使用新的CallbackRouter处理所有回调
            $router = new CallbackRouter(
                $this->languageService,
                $this->menuService,
                $this->startCommand,
                $this
            );

            $result = $router->route($user, $data, $messageId, $callbackQueryId);

            if (!$result) {
                $this->answerCallbackQuery($callbackQueryId, '未知操作');
            }

        } catch (\Exception $e) {
            Log::error('Callback query handling failed', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            $this->answerCallbackQuery($callbackQueryId, '处理失败');
        }
    }

    /**
     * 处理语言选择
     */
    private function handleLanguageSelection(TelegramUser $user, string $languageCode): void
    {
        try {
            // 设置用户语言
            $user->language = $languageCode;
            $user->language_selected = true;
            $user->save();

            // 发送语言变更提示
            $message = TelegramLanguageService::transForUser($user->telegram_user_id, 'language_changed');
            $this->sendMessage($user->chat_id, $message);

            // 调用 start 命令处理器，优先进入 welcome_message 子菜单
            try {
                $welcomeMenu = $this->menuService->findMenuItemByKey(self::DEFAULT_START_ROOT_KEY);
                if ($welcomeMenu) {
                    $this->startCommand->execute($user, ['callback_data' => 'lang_' . $languageCode], $welcomeMenu->id);
                    return;
                }

                // 找不到则回退到原有行为：无 menuItemId 的 start（显示根级）
                $commandFactory = new TelegramCommandFactory();
                $startCommand = $commandFactory->getCommandHandler('start');
                if ($startCommand) {
                    $startCommand->execute($user, ['callback_data' => 'lang_' . $languageCode]);
                }
            } catch (\Exception $e) {
                // 回退处理
                try {
                    $commandFactory = new TelegramCommandFactory();
                    $startCommand = $commandFactory->getCommandHandler('start');
                    if ($startCommand) {
                        $startCommand->execute($user, ['callback_data' => 'lang_' . $languageCode]);
                    }
                } catch (\Exception $fallbackError) {
                    Log::error('handleLanguageSelection: 回退处理也失败', [
                        'user_id' => $user->id,
                        'error' => $fallbackError->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('handleLanguageSelection: 语言选择处理过程中发生异常', [
                'user_id' => $user->id,
                'language_code' => $languageCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 发送语言选择
     */
    public function sendLanguageSelection(string $chatId): void
    {
        // 获取活跃语言，并预加载图片关联
        $languages = $this->languageService->getActiveLanguages()
            ->load(['languageImages.image']);

        // 如果用户没有选着语言，则默认使用is_default字段 else 使用用户当前语言
        $languagesDefault = $languages->where('is_default', true);

        // 尝试从数据库获取第一个语言的选择提示信息
        $firstLanguage = $languagesDefault->first();
        $title = null;
        $prompt = null;
        $selectionImageRelation = null;

        if ($firstLanguage && $firstLanguage->selection_title && $firstLanguage->selection_prompt) {
            $title = $firstLanguage->selection_title;
            $prompt = $firstLanguage->selection_prompt;

            // 通过关联获取selection类型的图片
            $selectionImageRelation = $firstLanguage->languageImages
                ->where('type', 'selection')
                ->first();
        }

        // 降级机制：如果数据库中没有数据，使用翻译文件
        if (!$title || !$prompt) {
            $title = __('telegram.language.multilingual_title', [], 'en');
            $prompt = __('telegram.language.multilingual_prompt', [], 'en');
        }

        // 构建键盘（每行 2 个语言按钮）
        $keyboard = [];
        $row = [];
        foreach ($languages as $language) {
            $row[] = [
                'text' => $language->native_name . ' (' . $language->name . ')',
                'callback_data' => 'lang_' . $language->code
            ];
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }
        if (count($row) > 0) {
            $keyboard[] = $row;
        }

        $inlineKeyboard = ['inline_keyboard' => $keyboard];
        $messageText = "{$title}\n\n{$prompt}";

        // 如果有图片，发送图片消息；否则发送文本消息
        if ($selectionImageRelation && $selectionImageRelation->image) {
            try {
                $image = $selectionImageRelation->image;
                $storagePath = storage_path('app/private/' . $image->path);

                // 检查文件是否存在
                if (file_exists($storagePath)) {
                    $inputFile = \Telegram\Bot\FileUpload\InputFile::create($storagePath, $image->filename);

                    Telegram::sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $inputFile,
                        'caption' => $messageText,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode($inlineKeyboard)
                    ]);
                } else {
                    // 文件不存在时发送文本消息
                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text' => $messageText,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode($inlineKeyboard)
                    ]);
                }
            } catch (\Exception $e) {
                // 图片上传失败时发送文本消息作为回退
                Log::error('sendLanguageSelection: Failed to send photo, falling back to text', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage()
                ]);

                Telegram::sendMessage([
                    'chat_id' => $chatId,
                    'text' => $messageText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
            }
        } else {
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $messageText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($inlineKeyboard)
            ]);
        }
    }



    /**
     * 发送默认回复
     */
    public function sendDefaultResponse(TelegramUser $user, string $text): void
    {
        $received = TelegramLanguageService::transForUser($user->telegram_user_id, 'messages.received');
        $supportReply = TelegramLanguageService::transForUser($user->telegram_user_id, 'messages.support_reply');
        $urgentHelp = TelegramLanguageService::transForUser($user->telegram_user_id, 'messages.urgent_help');

        $message = "{$received}\n\n{$supportReply}\n{$urgentHelp}";

        $this->sendMessage($user->chat_id, $message);
    }

    /**
     * 发送消息给特定用户
     */
    public function sendMessageToUser(int $userId, string $message, array $options = []): array
    {
        try {
            $telegramUser = TelegramUser::find($userId);

            if (!$telegramUser) {
                return [
                    'success' => false,
                    'error' => '用户不存在',
                    'code' => 'USER_NOT_FOUND'
                ];
            }

            if (!$telegramUser->is_active) {
                return [
                    'success' => false,
                    'error' => '用户已停用',
                    'code' => 'USER_INACTIVE'
                ];
            }

            // 创建用户消息记录
            $messageRecord = TelegramUserMessage::create([
                'user_id' => $userId,
                'message_text' => $message,
                'image_path' => $options['image_path'] ?? null,
                'keyboard' => $options['keyboard'] ?? null,
                'sent_by' => 'admin',
                'status' => 'pending'
            ]);

            // 直接发送消息（不通过队列）
            if (!empty($options['image_path'])) {
                // 发送带图片的消息
                $imagePath = \Illuminate\Support\Facades\Storage::path($options['image_path']);
                $params = [
                    'chat_id' => $telegramUser->chat_id,
                    'photo' => \Telegram\Bot\FileUpload\InputFile::create($imagePath),
                    'caption' => $message,
                    'parse_mode' => 'HTML'
                ];

                if (!empty($options['keyboard'])) {
                    // 转换键盘格式为 Telegram 内联键盘格式
                    $inlineKeyboard = $this->formatInlineKeyboard($options['keyboard']);
                    if (!empty($inlineKeyboard)) {
                        $params['reply_markup'] = json_encode([
                            'inline_keyboard' => $inlineKeyboard
                        ]);
                    }
                }

                $response = Telegram::sendPhoto($params);
            } else {
                // 发送纯文本消息
                $params = [
                    'chat_id' => $telegramUser->chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];

                if (!empty($options['keyboard'])) {
                    // 转换键盘格式为 Telegram 内联键盘格式
                    $inlineKeyboard = $this->formatInlineKeyboard($options['keyboard']);
                    if (!empty($inlineKeyboard)) {
                        $params['reply_markup'] = json_encode([
                            'inline_keyboard' => $inlineKeyboard
                        ]);
                    }
                }

                $response = Telegram::sendMessage($params);
            }

            // 更新消息记录
            $messageRecord->markAsSent($response['message_id']);

            // 更新用户最后交互时间
            $telegramUser->update(['last_interaction' => now()]);

            return [
                'success' => true,
                'message_id' => $response['message_id'],
                'message_record' => $messageRecord
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send message to user: ' . $e->getMessage(), [
                'user_id' => $userId,
                'message' => $message,
                'error' => $e
            ]);

            if (isset($messageRecord)) {
                $messageRecord->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'SEND_FAILED'
            ];
        }
    }



    /**
     * 发送消息的基础方法
     */
    private function sendMessage(string $chatId, string $message, array $options = []): void
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ], $options);

            Telegram::sendMessage($params);
        } catch (\Exception $e) {
            Log::error('sendMessage: 消息发送失败', [
                'chat_id' => $chatId,
                'message_length' => strlen($message),
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // 重新抛出异常以便上层处理
        }
    }

    /**
     * 应答回调查询
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = null): void
    {
        try {
            $params = [
                'callback_query_id' => $callbackQueryId
            ];

            if ($text) {
                $params['text'] = $text;
                $params['show_alert'] = false;
            }

            Telegram::answerCallbackQuery($params);
        } catch (\Exception $e) {
            Log::error('Failed to answer callback query: ' . $e->getMessage(), [
                'callback_query_id' => $callbackQueryId,
                'text' => $text
            ]);
        }
    }

    /**
     * 格式化内联键盘为 Telegram 格式
     */
    private function formatInlineKeyboard(array $keyboard): array
    {
        if (empty($keyboard)) {
            return [];
        }

        $inlineKeyboard = [];
        
        foreach ($keyboard as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $keyboardRow = [];
            foreach ($row as $button) {
                if (!is_array($button) || empty($button['text'])) {
                    continue;
                }
                
                $inlineButton = ['text' => $button['text']];
                
                if (!empty($button['url'])) {
                    $inlineButton['url'] = $button['url'];
                } elseif (!empty($button['callback_data'])) {
                    $inlineButton['callback_data'] = $button['callback_data'];
                }
                
                $keyboardRow[] = $inlineButton;
            }
            
            if (!empty($keyboardRow)) {
                $inlineKeyboard[] = $keyboardRow;
            }
        }
        
        return $inlineKeyboard;
    }

    // 旧的回调处理方法已被CallbackRouter替代
    // 这些方法已在架构重构中移除：
    // - handleMenuCallback()
    // - handleSpecificCallback()
    // - handleSpecialCallback()
    // 所有回调处理现在通过CallbackRouter统一管理
}
