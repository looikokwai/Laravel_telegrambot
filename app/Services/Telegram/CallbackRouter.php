<?php

namespace App\Services\Telegram;

use App\Models\TelegramUser;
use App\Models\TelegramMenuItem;
use App\Services\TelegramLanguageService;
use App\Services\TelegramMenuService;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\Commands\TelegramCommandFactory;
use Illuminate\Support\Facades\Log;

/**
 * 统一的Telegram回调路由器
 * 负责将不同类型的回调分发到对应的处理器
 */
class CallbackRouter
{
    private TelegramLanguageService $languageService;
    private TelegramMenuService $menuService;
    private StartCommand $startCommand;
    private $messageService;

    public function __construct(
        TelegramLanguageService $languageService,
        TelegramMenuService $menuService,
        StartCommand $startCommand,
        $messageService = null
    ) {
        $this->languageService = $languageService;
        $this->menuService = $menuService;
        $this->startCommand = $startCommand;
        $this->messageService = $messageService;
    }

    /**
     * 路由回调到对应的处理器
     *
     * @param TelegramUser $user
     * @param string $data
     * @param int $messageId
     * @param string $callbackQueryId
     * @return bool
     */
    public function route(TelegramUser $user, string $data, int $messageId, string $callbackQueryId): bool
    {
        try {
            // 语言选择回调
            if (str_starts_with($data, 'lang_')) {
                return $this->handleLanguageCallback($user, $data, $callbackQueryId);
            }

            // 命令回调
            if (str_starts_with($data, 'cmd_')) {
                return $this->handleCommandCallback($user, $data, $messageId, $callbackQueryId);
            }

            // 格式化的菜单回调（menu_xxx格式）
            if (str_starts_with($data, 'menu_')) {
                return $this->handleFormattedMenuCallback($user, $data, $callbackQueryId);
            }

            // 默认菜单处理（查找数据库中的菜单项）
            return $this->handleDefaultMenuCallback($user, $data, $callbackQueryId);

        } catch (\Exception $e) {
            Log::error('CallbackRouter: Route processing failed', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 处理语言选择回调
     */
    private function handleLanguageCallback(TelegramUser $user, string $data, string $callbackQueryId): bool
    {
        $languageCode = substr($data, 5); // 移除 'lang_' 前缀

        // 调用TelegramMessageService的handleLanguageSelection方法
        if ($this->messageService && method_exists($this->messageService, 'handleLanguageSelection')) {
            // 使用反射调用私有方法
            $reflection = new \ReflectionClass($this->messageService);
            $method = $reflection->getMethod('handleLanguageSelection');
            $method->setAccessible(true);
            $method->invoke($this->messageService, $user, $languageCode);
        } else {
            // 备用处理：直接更新用户语言
            $user->language = $languageCode;
            $user->language_selected = true;
            $user->save();
        }

        // 发送确认消息
        if ($this->messageService) {
            $this->messageService->answerCallbackQuery($callbackQueryId, '语言已更新');
        }

        return true;
    }

    /**
     * 处理命令回调
     */
    private function handleCommandCallback(TelegramUser $user, string $data, int $messageId, string $callbackQueryId): bool
    {
        $command = '/' . substr($data, 4); // 移除 'cmd_' 前缀并添加 '/' 前缀

        // 使用TelegramCommandFactory处理命令
        $commandFactory = new TelegramCommandFactory();
        $commandHandler = $commandFactory->getCommandHandler($command);

        if ($commandHandler) {
            try {
                $message = ['callback_data' => $data, 'message_id' => $messageId];

                // 特殊处理 /start 命令，默认显示 welcome_message 的子菜单
                if ($command === '/start') {
                    // 查找 welcome_message 菜单项
                    $welcomeMenuItem = $this->menuService->findMenuItemByKey('welcome_message');
                    if ($welcomeMenuItem) {
                        // 由于接口限制，命令处理器execute不接受menuId，这里直接用StartCommand实例
                        $this->startCommand->execute($user, $message, $welcomeMenuItem->id);
                    } else {
                        $commandHandler->execute($user, $message);
                    }
                } else {
                    $commandHandler->execute($user, $message);
                }

                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId);
                }
                return true;
            } catch (\Exception $e) {
                Log::error('CallbackRouter: Command execution failed', [
                    'command' => $command,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId, '命令执行失败');
                }
                return false;
            }
        } else {
            Log::warning('CallbackRouter: Unknown command', ['command' => $command]);

            if ($this->messageService) {
                $this->messageService->answerCallbackQuery($callbackQueryId, '未知命令');
            }
            return false;
        }
    }

    /**
     * 处理格式化的菜单回调（menu_xxx格式）
     */
    private function handleFormattedMenuCallback(TelegramUser $user, string $data, string $callbackQueryId): bool
    {
        $menuKey = substr($data, 5); // 移除 'menu_' 前缀

        // 查找菜单项
        $menuItem = TelegramMenuItem::where('key', $menuKey)
            ->orWhere('callback_data', $data)
            ->active()
            ->first();

        if ($menuItem) {
            return $this->processMenuItemCallback($user, $menuItem, $data, $callbackQueryId);
        }

        return false;
    }

    /**
     * 处理默认菜单回调（查找数据库中的菜单项）
     */
    private function handleDefaultMenuCallback(TelegramUser $user, string $data, string $callbackQueryId): bool
    {

        // 查找菜单项
        $menuItem = TelegramMenuItem::where('callback_data', $data)
            ->orWhere('key', $data)
            ->active()
            ->first();

        if ($menuItem) {
            return $this->processMenuItemCallback($user, $menuItem, $data, $callbackQueryId);
        }

        // 如果不是菜单项，尝试特殊回调处理
        return $this->handleSpecialCallback($user, $data, $callbackQueryId);
    }

    /**
     * 处理菜单项回调
     */
    private function processMenuItemCallback(TelegramUser $user, TelegramMenuItem $menuItem, string $data, string $callbackQueryId): bool
    {

        switch ($menuItem->type) {
            case 'submenu':
                // 显示子菜单
                $this->startCommand->execute($user, ['callback_data' => $data], $menuItem->id);
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId);
                }
                break;

            case 'callback':
                // 处理回调动作
                $this->processCallbackAction($user, $menuItem, $callbackQueryId);
                break;

            case 'button':
                // 处理按钮动作
                $this->processButtonAction($user, $menuItem, $callbackQueryId);
                break;

            default:
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId, '操作已执行');
                }
                break;
        }

        // 记录菜单统计
        $this->menuService->recordMenuStat($menuItem->id, $user->id, 'click');
        return true;
    }

    /**
     * 处理回调动作
     */
    private function processCallbackAction(TelegramUser $user, TelegramMenuItem $menuItem, string $callbackQueryId): bool
    {

        // 特殊动作：更换语言（callback_data 或 key 为 language）
        if (($menuItem->callback_data === 'language') || ($menuItem->key === 'language')) {
            if ($this->messageService) {
                $this->messageService->answerCallbackQuery($callbackQueryId);
                $this->messageService->sendLanguageSelection($user->chat_id);
            }
            return true;
        }

        // 简短回调提示 + 发送详细信息（标题+描述）
        if ($this->messageService) {
            $this->messageService->answerCallbackQuery($callbackQueryId);
            $language = $this->languageService->getLanguageByCode($user->language ?? '');
            $languageId = $language ? $language->id : null;
            $this->messageService->sendMenuItemInfo($user, $menuItem, $languageId);
        }

        return true;
    }

    /**
     * 处理按钮动作
     */
    private function processButtonAction(TelegramUser $user, TelegramMenuItem $menuItem, string $callbackQueryId): bool
    {

        // 简短回调提示 + 发送详细信息（标题+描述）
        if ($this->messageService) {
            $this->messageService->answerCallbackQuery($callbackQueryId);
            $language = $this->languageService->getLanguageByCode($user->language ?? '');
            $languageId = $language ? $language->id : null;
            $this->messageService->sendMenuItemInfo($user, $menuItem, $languageId);
        }

        return true;
    }

    /**
     * 处理特殊回调（不在数据库菜单项中的回调）
     */
    private function handleSpecialCallback(TelegramUser $user, string $data, string $callbackQueryId): bool
    {

        // 新协议：back_to_{parentId} 或 back_to_root
        if (str_starts_with($data, 'back_to_')) {
            $target = substr($data, 8);
            if ($target === 'root') {
                $this->startCommand->execute($user, ['callback_data' => $data]);
            } else {
                $parentId = (int)$target;
                if ($parentId > 0) {
                    $this->startCommand->execute($user, ['callback_data' => $data], $parentId);
                } else {
                    $this->startCommand->execute($user, ['callback_data' => $data]);
                }
            }
            if ($this->messageService) {
                $this->messageService->answerCallbackQuery($callbackQueryId);
            }
            return true;
        }

        switch ($data) {
            case 'back_to_parent':
                // 处理返回父级菜单回调
                // 这是一个特殊回调，不需要在数据库中存储
                // 直接显示根菜单
                $this->startCommand->execute($user, ['callback_data' => $data]);
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId);
                }
                return true;

            case 'help':
                // 处理帮助回调
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId, '帮助信息');
                }
                return true;

            case 'about':
                // 处理关于回调
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId, '关于信息');
                }
                return true;

            default:
                if ($this->messageService) {
                    $this->messageService->answerCallbackQuery($callbackQueryId, '未知操作');
                }
                return false;
        }
    }
}
