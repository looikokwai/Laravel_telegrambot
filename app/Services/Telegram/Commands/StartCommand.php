<?php

namespace App\Services\Telegram\Commands;

use App\Models\TelegramUser;
use App\Services\TelegramMenuService;
use App\Services\TelegramLanguageService;

/**
 * Start命令处理器
 */
class StartCommand extends AbstractTelegramCommand
{
    protected TelegramMenuService $menuService;
    protected TelegramLanguageService $languageService;

    public function __construct(
        TelegramMenuService $menuService,
        TelegramLanguageService $languageService
    ) {
        $this->menuService = $menuService;
        $this->languageService = $languageService;
    }

    /**
     * 执行start命令
     *
     * @param TelegramUser $user
     * @param array $message
     * @param int|null $menuItemId 要显示的菜单项ID，null表示显示根菜单
     * @return array
     */
    public function execute(TelegramUser $user, array $message, int $menuItemId = null): array
    {
        // 检测用户语言偏好
        $userLanguage = $this->languageService->detectUserLanguagePreference(
            $user->id,
            $message['from']['language_code'] ?? null
        );

        // 根据菜单项ID决定显示内容
        if ($menuItemId === null) {
            // 显示根菜单和欢迎消息
            $welcomeMessage = $this->trans($user, 'welcome_message');
            $keyboard = $this->createDynamicMenuKeyboard($user, $userLanguage, null);
            $action = 'start_menu_shown';
        } else {
            // 显示指定菜单项的子菜单
            $menuItem = $this->menuService->findMenuItemById($menuItemId);
            if (!$menuItem) {
                return [
                    'success' => false,
                    'message' => 'Menu item not found'
                ];
            }

            // 获取菜单项的翻译，展示 标题+描述
            $translation = $menuItem->getTranslation($userLanguage ? $userLanguage->id : null);
            $title = $translation ? ($translation->title ?? $menuItem->key) : $menuItem->key;
            $desc = $translation ? ($translation->description ?? null) : null;
            $welcomeMessage = $desc
                ? ("<b>" . e($title) . "</b>\n\n" . e($desc))
                : e($title);

            $keyboard = $this->createDynamicMenuKeyboard($user, $userLanguage, $menuItemId);
            $action = 'submenu_shown';
        }

        // 发送消息（优先图片 + caption，其次纯文本）
        $result = false;
        try {
            if (isset($menuItem)) {
                $languageId = $userLanguage ? $userLanguage->id : null;
                $imageRelation = $menuItem->getImage($languageId, 'banner')
                    ?? $menuItem->getImage($languageId, 'icon')
                    ?? $menuItem->getImage(null, 'banner')
                    ?? $menuItem->getImage(null, 'icon');

                if ($imageRelation && $imageRelation->image) {
                    $image = $imageRelation->image;
                    $storagePath = storage_path('app/private/' . $image->path);
                    if (file_exists($storagePath)) {
                        $inputFile = \Telegram\Bot\FileUpload\InputFile::create($storagePath, $image->filename);
                        \Telegram\Bot\Laravel\Facades\Telegram::sendPhoto([
                            'chat_id' => $user->chat_id,
                            'photo' => $inputFile,
                            'caption' => $welcomeMessage,
                            'parse_mode' => 'HTML',
                            'reply_markup' => json_encode($keyboard)
                        ]);
                        $result = true;
                    }
                }
            }
        } catch (\Exception $e) {
            // 图片发送失败，静默处理
        }

        if ($result === false) {
            // 回退为纯文本
            $result = $this->sendMessage($user, $welcomeMessage, $keyboard);
        }

        // 记录菜单统计
        $this->recordMenuStats($user, $action, $menuItemId);

        return [
            'success' => $result,
            'message' => $welcomeMessage
        ];
    }

    /**
     * 创建动态菜单键盘
     *
     * @param TelegramUser $user
     * @param \App\Models\TelegramLanguage|null $language
     * @param int|null $parentMenuItemId 父菜单项ID，null表示获取根菜单
     * @return array
     */
    private function createDynamicMenuKeyboard(TelegramUser $user, $language = null, int $parentMenuItemId = null): array
    {
        try {
            $languageId = $language ? $language->id : null;

            if ($parentMenuItemId === null) {
                // 获取根级菜单项
                $menuItems = $this->menuService->getRootMenuItems($languageId);
            } else {
                // 获取指定菜单项的子菜单
                $menuItems = $this->menuService->getMenuChildren($parentMenuItemId, $languageId);
            }

            // 使用菜单服务构建Telegram键盘
            $keyboard = $this->menuService->buildTelegramKeyboard($menuItems, $languageId);

            // 如果是子菜单，添加返回按钮（返回到上一级父菜单；若已是根的子级，则返回根级）
            if ($parentMenuItemId !== null) {
                $targetParentId = null;
                $current = null;
                try {
                    $current = $this->menuService->findMenuItemById($parentMenuItemId);
                    $targetParentId = $current ? $current->parent_id : null;
                } catch (\Exception $e) {
                    $current = null;
                }

                // 当当前菜单是根级（如欢迎菜单）时，不显示返回按钮
                if ($current && $current->parent_id !== null) {
                    $callbackData = $targetParentId ? ('back_to_' . $targetParentId) : 'back_to_root';

                    $backButton = [
                        'text' => $this->getBackLabel($languageId),
                        'callback_data' => $callbackData
                    ];
                    $keyboard[] = [$backButton];
                }
            }

            return ['inline_keyboard' => $keyboard];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create dynamic menu: ' . $e->getMessage());

            // 如果动态菜单失败，回退到静态菜单
            return $this->createFallbackMenuKeyboard($user);
        }
    }

    /**
     * 获取返回按钮文案，按语言读取，默认“🔙 返回”
     */
    private function getBackLabel(?int $languageId): string
    {
        try {
            if ($languageId) {
                $lang = \App\Models\TelegramLanguage::find($languageId);
                if ($lang && !empty($lang->back_label)) {
                    return $lang->back_label;
                }
            } else {
                // 若无具体 languageId，尝试默认语言
                $default = \App\Models\TelegramLanguage::where('is_default', true)->first();
                if ($default && !empty($default->back_label)) {
                    return $default->back_label;
                }
            }
        } catch (\Exception $e) {
            // 获取返回按钮文案失败，使用默认值
        }
        return '🔙 返回';
    }

    /**
     * 创建回退菜单键盘（当动态菜单失败时使用）
     *
     * @param TelegramUser $user
     * @return array
     */
    private function createFallbackMenuKeyboard(TelegramUser $user): array
    {
        // 简单的回退菜单
        $buttons = [
            [
                [
                    'text' => '📚 帮助',
                    'callback_data' => 'help'
                ],
                [
                    'text' => '🌐 语言',
                    'callback_data' => 'language'
                ]
            ],
            [
                [
                    'text' => '📞 联系我们',
                    'callback_data' => 'contact'
                ]
            ]
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * 记录菜单统计
     *
     * @param TelegramUser $user
     * @param string $action
     * @param int|null $menuItemId
     * @return void
     */
    private function recordMenuStats(TelegramUser $user, string $action, int $menuItemId = null): void
    {
        try {
            // 在命令行环境中，session可能不可用，使用备用方案
            $sessionId = 'telegram_' . $user->telegram_user_id;
            if (function_exists('session') && session()->isStarted()) {
                $sessionId = session()->getId() ?? $sessionId;
            }

            $this->menuService->recordMenuStat(
                $menuItemId,
                $user->id,
                $action,
                $sessionId,
                [
                    'command' => 'start',
                    'user_agent' => 'Telegram Bot',
                    'timestamp' => now()->toISOString(),
                    'menu_item_id' => $menuItemId
                ]
            );
        } catch (\Exception $e) {
            // 记录菜单统计失败，静默处理
        }
    }

    /**
     * 获取命令名称
     *
     * @return string
     */
    public function getCommandName(): string
    {
        return 'start';
    }

    /**
     * 获取命令描述
     *
     * @return string
     */
    public function getDescription(): string
    {
        return '开始使用机器人';
    }
}
