<?php

namespace App\Services;

use App\Models\TelegramUser;
use Illuminate\Support\Collection;

class TelegramUserService
{
    /**
     * 保存或更新Telegram用户信息
     */
    public function saveOrUpdateUser(
        string $userId,
        string $chatId,
        ?string $username,
        ?string $firstName,
        ?string $lastName = null,
        ?string $languageCode = null
    ): TelegramUser {
        $userData = [
            'chat_id' => $chatId,
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
            'last_interaction' => now()
        ];

        // 如果用户还没有选择语言，尝试从Telegram检测
        $existingUser = TelegramUser::where('telegram_user_id', $userId)->first();
        if (!$existingUser || !$existingUser->language_selected) {
            $languageService = app(TelegramLanguageService::class);
            // 只有当languageCode不为null时才尝试检测
            if ($languageCode) {
                $detectedLanguage = $languageService->detectLanguageFromTelegram($languageCode);
                $userData['language'] = $detectedLanguage ? $detectedLanguage->code : 'en';
            } else {
                // 如果没有语言代码，使用默认语言
                $userData['language'] = 'en';
            }
        }

        return TelegramUser::updateOrCreate(
            ['telegram_user_id' => $userId],
            $userData
        );
    }

    /**
     * 根据Telegram ID查找用户
     */
    public function findByTelegramId(string $telegramId): ?TelegramUser
    {
        return TelegramUser::where('telegram_user_id', $telegramId)->first();
    }

    /**
     * 根据Chat ID查找用户
     */
    public function findByChatId(string $chatId): ?TelegramUser
    {
        return TelegramUser::where('chat_id', $chatId)->first();
    }

    /**
     * 获取活跃用户
     */
    public function getActiveUsers(): Collection
    {
        return TelegramUser::active()->get();
    }

    /**
     * 获取最近活跃用户
     */
    public function getRecentlyActiveUsers(int $days = 7): Collection
    {
        return TelegramUser::recentlyActive($days)->get();
    }

    /**
     * 按语言分组获取用户
     */
    public function getUsersByLanguage(string $language): Collection
    {
        return TelegramUser::where('language', $language)
            ->active()
            ->get();
    }

    /**
     * 获取所有用户按语言分组
     */
    public function getAllUsersByLanguage(): Collection
    {
        return TelegramUser::active()
            ->get()
            ->groupBy('language');
    }

    /**
     * 设置用户语言
     */
    public function setUserLanguage(string $telegramId, string $language): bool
    {
        return TelegramUser::where('telegram_user_id', $telegramId)
            ->update([
                'language' => $language,
                'language_selected' => true
            ]) > 0;
    }

    /**
     * 设置用户状态
     */
    public function setUserStatus(string $telegramId, bool $isActive): bool
    {
        return TelegramUser::where('telegram_user_id', $telegramId)
            ->update(['is_active' => $isActive]) > 0;
    }

    /**
     * 更新用户最后交互时间
     */
    public function updateLastInteraction(string $telegramId): bool
    {
        return TelegramUser::where('telegram_user_id', $telegramId)
            ->update(['last_interaction' => now()]) > 0;
    }

    /**
     * 获取用户统计信息
     */
    public function getUserStats(): array
    {
        $total = TelegramUser::count();
        $active = TelegramUser::active()->count();
        $languageStats = TelegramUser::selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->pluck('count', 'language')
            ->toArray();

        return [
            'total_users' => $total,
            'active_users' => $active,
            'inactive_users' => $total - $active,
            'language_distribution' => $languageStats,
            'users_with_language_selected' => TelegramUser::where('language_selected', true)->count()
        ];
    }

    /**
     * 关联Telegram用户到系统用户
     */
    public function linkToSystemUser(string $telegramId, int $userId): bool
    {
        return TelegramUser::where('telegram_user_id', $telegramId)
            ->update(['user_id' => $userId]) > 0;
    }

    /**
     * 取消关联系统用户
     */
    public function unlinkFromSystemUser(string $telegramId): bool
    {
        return TelegramUser::where('telegram_user_id', $telegramId)
            ->update(['user_id' => null]) > 0;
    }
}