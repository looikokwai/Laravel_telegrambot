<?php

namespace App\Services;

use App\Models\TelegramLanguage;
use App\Models\TelegramMenuTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramLanguageService
{
    private const CACHE_PREFIX = 'telegram_language_';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * 获取所有活跃语言
     */
    public function getActiveLanguages(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'active_languages',
            self::CACHE_TTL,
            fn() => TelegramLanguage::active()->ordered()->get()
        );
    }

    /**
     * 根据代码获取语言
     */
    public function getLanguageByCode(string $code): ?TelegramLanguage
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'by_code_' . $code,
            self::CACHE_TTL,
            fn() => TelegramLanguage::where('code', $code)->first()
        );
    }

    /**
     * 获取默认语言
     */
    public function getDefaultLanguage(): ?TelegramLanguage
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'default',
            self::CACHE_TTL,
            fn() => TelegramLanguage::where('is_default', true)->first()
        );
    }

    /**
     * 创建语言
     */
    public function createLanguage(array $data): TelegramLanguage
    {
        try {
            // 如果设置为默认语言，先取消其他语言的默认状态
            if ($data['is_default'] ?? false) {
                $this->unsetDefaultLanguage();
            }

            $language = TelegramLanguage::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'native_name' => $data['native_name'] ?? null,
                'flag_emoji' => $data['flag_emoji'] ?? null,
                'is_rtl' => $data['is_rtl'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
                'selection_title' => $data['selection_title'] ?? null,
                'selection_prompt' => $data['selection_prompt'] ?? null,
                'back_label' => $data['back_label'] ?? null,
            ]);

            $this->clearLanguageCache();

            return $language;
        } catch (\Exception $e) {
            Log::error('Failed to create language: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新语言
     */
    public function updateLanguage(int $id, array $data): TelegramLanguage
    {
        try {
            $language = TelegramLanguage::findOrFail($id);

            // 如果设置为默认语言，先取消其他语言的默认状态
            if (($data['is_default'] ?? false) && !$language->is_default) {
                $this->unsetDefaultLanguage();
            }

            $language->update([
                'name' => $data['name'] ?? $language->name,
                'code' => $data['code'] ?? $language->code,
                'native_name' => $data['native_name'] ?? $language->native_name,
                'flag_emoji' => $data['flag_emoji'] ?? $language->flag_emoji,
                'is_rtl' => $data['is_rtl'] ?? $language->is_rtl,
                'is_active' => $data['is_active'] ?? $language->is_active,
                'is_default' => $data['is_default'] ?? $language->is_default,
                'sort_order' => $data['sort_order'] ?? $language->sort_order,
                'selection_title' => $data['selection_title'] ?? $language->selection_title,
                'selection_prompt' => $data['selection_prompt'] ?? $language->selection_prompt,
                'back_label' => $data['back_label'] ?? $language->back_label,
            ]);

            $this->clearLanguageCache();

            return $language->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update language: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除语言
     */
    public function deleteLanguage(int $id): bool
    {
        try {
            $language = TelegramLanguage::findOrFail($id);

            // 不能删除默认语言
            if ($language->is_default) {
                throw new \Exception('Cannot delete default language');
            }

            // 删除相关翻译
            TelegramMenuTranslation::where('language_id', $id)->delete();

            // 删除语言
            $language->delete();

            $this->clearLanguageCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete language: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 设置默认语言
     */
    public function setDefaultLanguage(int $id): bool
    {
        try {
            // 取消所有语言的默认状态
            $this->unsetDefaultLanguage();

            // 设置新的默认语言
            TelegramLanguage::where('id', $id)->update(['is_default' => true]);

            $this->clearLanguageCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set default language: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 切换语言状态
     */
    public function toggleLanguageStatus(int $id): bool
    {
        try {
            $language = TelegramLanguage::findOrFail($id);

            // 不能禁用默认语言
            if ($language->is_default && $language->is_active) {
                throw new \Exception('Cannot disable default language');
            }

            $language->update(['is_active' => !$language->is_active]);

            $this->clearLanguageCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to toggle language status: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新语言排序
     */
    public function updateLanguageOrder(array $orderData): bool
    {
        try {
            foreach ($orderData as $item) {
                TelegramLanguage::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            $this->clearLanguageCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update language order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取用户首选语言
     */
    public function getUserPreferredLanguage(int $userId): ?TelegramLanguage
    {
        // 这里可以实现用户语言偏好逻辑
        // 暂时返回默认语言
        return $this->getDefaultLanguage();
    }

    /**
     * 检测用户语言偏好
     */
    public function detectUserLanguagePreference(int $userId, ?string $telegramLanguageCode = null): ?TelegramLanguage
    {
        // 首先检查用户是否已经选择了语言
        $user = \App\Models\TelegramUser::find($userId);
        if ($user && $user->language_selected && $user->language) {
            $userLanguage = $this->getLanguageByCode($user->language);
            if ($userLanguage && $userLanguage->is_active) {
                return $userLanguage;
            }
        }

        // 如果用户没有选择语言，尝试根据 Telegram 语言代码匹配
        if ($telegramLanguageCode) {
            $language = $this->getLanguageByCode($telegramLanguageCode);
            if ($language && $language->is_active) {
                return $language;
            }
        }

        // 如果没有匹配，返回默认语言
        return $this->getDefaultLanguage();
    }

    /**
     * 从Telegram检测语言
     */
    public function detectLanguageFromTelegram(string $telegramLanguageCode): ?TelegramLanguage
    {
        // 尝试根据 Telegram 语言代码匹配
        $language = $this->getLanguageByCode($telegramLanguageCode);

        if ($language && $language->is_active) {
            return $language;
        }

        // 如果没有匹配，返回默认语言
        return $this->getDefaultLanguage();
    }

    /**
     * 获取翻译完成度统计
     */
    public function getTranslationCompletionStats(): array
    {
        $languages = $this->getActiveLanguages();
        $totalMenuItems = \App\Models\TelegramMenuItem::count();

        $stats = [];

        foreach ($languages as $language) {
            $translatedItems = TelegramMenuTranslation::where('language_id', $language->id)->count();
            $completionRate = $totalMenuItems > 0 ? ($translatedItems / $totalMenuItems) * 100 : 0;

            $stats[] = [
                'language' => $language,
                'translated_items' => $translatedItems,
                'total_items' => $totalMenuItems,
                'completion_rate' => round($completionRate, 2)
            ];
        }

        return $stats;
    }

    /**
     * 获取缺失的翻译
     */
    public function getMissingTranslations(int $languageId = null): Collection
    {
        $languages = $languageId
            ? TelegramLanguage::where('id', $languageId)->get()
            : $this->getActiveLanguages();

        $missing = collect();

        foreach ($languages as $language) {
            $menuItems = \App\Models\TelegramMenuItem::active()->get();

            foreach ($menuItems as $menuItem) {
                $translation = TelegramMenuTranslation::where('menu_item_id', $menuItem->id)
                    ->where('language_id', $language->id)
                    ->first();

                if (!$translation) {
                    $missing->push([
                        'menu_item' => $menuItem,
                        'language' => $language,
                        'missing_fields' => ['title', 'description']
                    ]);
                }
            }
        }

        return $missing;
    }

    /**
     * 批量创建翻译
     */
    public function batchCreateTranslations(array $translations): int
    {
        $created = 0;

        foreach ($translations as $translation) {
            try {
                $this->createOrUpdateTranslation(
                    $translation['menu_item_id'],
                    $translation['language_id'],
                    $translation
                );
                $created++;
            } catch (\Exception $e) {
                Log::error('Failed to create translation: ' . $e->getMessage());
            }
        }

        return $created;
    }

    /**
     * 导出语言数据
     */
    public function exportLanguageData(int $languageId = null): array
    {
        $query = TelegramLanguage::with(['translations.menuItem']);

        if ($languageId) {
            $query->where('id', $languageId);
        }

        return $query->get()->toArray();
    }

    /**
     * 导入语言数据
     */
    public function importLanguageData(array $data): bool
    {
        try {
            foreach ($data as $languageData) {
                $language = TelegramLanguage::updateOrCreate(
                    ['code' => $languageData['code']],
                    [
                        'name' => $languageData['name'],
                        'native_name' => $languageData['native_name'],
                        'is_active' => $languageData['is_active'] ?? true,
                        'sort_order' => $languageData['sort_order'] ?? 0,
                    ]
                );

                // 导入翻译
                if (isset($languageData['translations'])) {
                    foreach ($languageData['translations'] as $translationData) {
                        $this->createOrUpdateTranslation(
                            $translationData['menu_item_id'],
                            $language->id,
                            $translationData
                        );
                    }
                }
            }

            $this->clearLanguageCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to import language data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 创建或更新翻译
     */
    public function createOrUpdateTranslation(int $menuItemId, int $languageId, array $data): TelegramMenuTranslation
    {
        return TelegramMenuTranslation::updateOrCreate(
            [
                'menu_item_id' => $menuItemId,
                'language_id' => $languageId,
            ],
            [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]
        );
    }

    /**
     * 获取语言统计
     */
    public function getLanguageStats(int $languageId): array
    {
        return [
            'total_translations' => TelegramMenuTranslation::where('language_id', $languageId)->count(),
            'menu_items_count' => TelegramMenuTranslation::where('language_id', $languageId)
                ->distinct('menu_item_id')->count(),
        ];
    }

    /**
     * 清除语言缓存
     */
    public function clearLanguageCache(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'active_languages');
        Cache::forget(self::CACHE_PREFIX . 'default');

        // 清除按代码缓存的语言
        $languages = TelegramLanguage::pluck('code');
        foreach ($languages as $code) {
            Cache::forget(self::CACHE_PREFIX . 'by_code_' . $code);
        }
    }

    /**
     * 为用户获取翻译内容
     */
    public static function transForUser(string $telegramUserId, string $key): string
    {
        try {
            // 获取用户信息
            $user = \App\Models\TelegramUser::where('telegram_user_id', $telegramUserId)->first();
            if (!$user) {
                return $key; // 如果用户不存在，返回原始key
            }

            // 获取用户语言
            $language = \App\Models\TelegramLanguage::where('code', $user->language)->first();
            if (!$language) {
                // 如果用户语言不存在，使用默认语言
                $language = \App\Models\TelegramLanguage::where('is_default', true)->first();
            }

            if (!$language) {
                return $key; // 如果连默认语言都不存在，返回原始key
            }

            // 解析翻译key，格式可能是 "menu_item.field" 或直接是菜单项标识
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $menuItemKey = $parts[0];
                $field = $parts[1]; // title 或 description
            } else {
                $menuItemKey = $key;
                $field = 'title'; // 默认获取标题
            }

            // 查找菜单项
            $menuItem = \App\Models\TelegramMenuItem::where('key', $menuItemKey)->first();
            if (!$menuItem) {
                return $key; // 如果菜单项不存在，返回原始key
            }

            // 查找翻译
            $translation = TelegramMenuTranslation::where('menu_item_id', $menuItem->id)
                ->where('language_id', $language->id)
                ->first();

            if ($translation && isset($translation->$field)) {
                return $translation->$field;
            }

            // 如果没有找到翻译，尝试使用默认语言的翻译
            if (!$language->is_default) {
                $defaultLanguage = \App\Models\TelegramLanguage::where('is_default', true)->first();
                if ($defaultLanguage) {
                    $defaultTranslation = TelegramMenuTranslation::where('menu_item_id', $menuItem->id)
                        ->where('language_id', $defaultLanguage->id)
                        ->first();

                    if ($defaultTranslation && isset($defaultTranslation->$field)) {
                        return $defaultTranslation->$field;
                    }
                }
            }

            return $key; // 最后返回原始key
        } catch (\Exception $e) {
            Log::error('Failed to get translation for user: ' . $e->getMessage());
            return $key;
        }
    }

    /**
     * 检查用户是否已选择语言
     */
    public static function hasUserSelectedLanguage(string $telegramUserId): bool
    {
        $user = \App\Models\TelegramUser::where('telegram_user_id', $telegramUserId)->first();
        return $user ? $user->language_selected : false;
    }

    /**
     * 设置用户语言
     */
    public static function setUserLanguage(string $telegramUserId, string $languageCode): bool
    {
        $user = \App\Models\TelegramUser::where('telegram_user_id', $telegramUserId)->first();
        if (!$user) {
            return false;
        }

        $language = \App\Models\TelegramLanguage::where('code', $languageCode)->first();
        if (!$language) {
            return false;
        }

        $user->language = $languageCode;
        $user->language_selected = true;
        return $user->save();
    }

    /**
     * 获取用户语言
     */
    public static function getUserLanguage(string $telegramUserId): string
    {
        $user = \App\Models\TelegramUser::where('telegram_user_id', $telegramUserId)->first();
        return $user ? $user->language : 'en';
    }

    /**
     * 生成语言选择键盘
     */
    public static function getLanguageKeyboard(): array
    {
        $languages = \App\Models\TelegramLanguage::active()->ordered()->get();
        $keyboard = [];

        foreach ($languages as $language) {
            $keyboard[] = [[
                'text' => $language->flag_emoji . ' ' . $language->native_name,
                'callback_data' => 'lang_' . $language->code
            ]];
        }

        return $keyboard;
    }

    /**
     * 获取指定语言的翻译内容
     */
    public static function trans(string $key, array $parameters = [], string $languageCode = null): string
    {
        try {
            // 如果没有指定语言代码，使用默认语言
            if (!$languageCode) {
                $language = \App\Models\TelegramLanguage::where('is_default', true)->first();
            } else {
                $language = \App\Models\TelegramLanguage::where('code', $languageCode)->first();
            }

            if (!$language) {
                return $key; // 如果语言不存在，返回原始key
            }

            // 解析翻译key，格式可能是 "menu_item.field" 或直接是菜单项标识
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $menuItemKey = $parts[0];
                $field = $parts[1]; // title 或 description
            } else {
                $menuItemKey = $key;
                $field = 'title'; // 默认获取标题
            }

            // 查找菜单项
            $menuItem = \App\Models\TelegramMenuItem::where('key', $menuItemKey)->first();
            if (!$menuItem) {
                return $key; // 如果菜单项不存在，返回原始key
            }

            // 查找翻译
            $translation = TelegramMenuTranslation::where('menu_item_id', $menuItem->id)
                ->where('language_id', $language->id)
                ->first();

            if ($translation && isset($translation->$field)) {
                $message = $translation->$field;

                // 替换参数
                foreach ($parameters as $key => $value) {
                    $message = str_replace(":{$key}", $value, $message);
                }

                return $message;
            }

            // 如果没有找到翻译，尝试使用默认语言的翻译
            if (!$language->is_default) {
                $defaultLanguage = \App\Models\TelegramLanguage::where('is_default', true)->first();
                if ($defaultLanguage) {
                    $defaultTranslation = TelegramMenuTranslation::where('menu_item_id', $menuItem->id)
                        ->where('language_id', $defaultLanguage->id)
                        ->first();

                    if ($defaultTranslation && isset($defaultTranslation->$field)) {
                        $message = $defaultTranslation->$field;

                        // 替换参数
                        foreach ($parameters as $key => $value) {
                            $message = str_replace(":{$key}", $value, $message);
                        }

                        return $message;
                    }
                }
            }

            return $key; // 最后返回原始key
        } catch (\Exception $e) {
            Log::error('Failed to get translation: ' . $e->getMessage());
            return $key;
        }
    }

    /**
     * 取消默认语言设置
     */
    private function unsetDefaultLanguage(): void
    {
        TelegramLanguage::where('is_default', true)->update(['is_default' => false]);
    }
}
