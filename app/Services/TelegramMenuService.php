<?php

namespace App\Services;

use App\Models\TelegramMenuItem;
use App\Models\TelegramMenuTranslation;
use App\Models\TelegramLanguage;
use App\Models\TelegramMenuStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramMenuService
{
    /**
     * 获取根级菜单项
     */
    public function getRootMenuItems(int $languageId = null): Collection
    {
        $cacheKey = "telegram_menu_root_{$languageId}";

        return Cache::remember($cacheKey, 3600, function () use ($languageId) {
            $query = TelegramMenuItem::root()
                ->active()
                ->ordered()
                ->with(['translations', 'children.translations']);

            if ($languageId) {
                $query->with([
                    'translations' => function ($q) use ($languageId) {
                        $q->where('language_id', $languageId);
                    },
                    'children.translations' => function ($q) use ($languageId) {
                        $q->where('language_id', $languageId);
                    }
                ]);
            }

            return $query->get();
        });
    }

    /**
     * 获取菜单项的子项
     */
    public function getMenuChildren(int $parentId, int $languageId = null): Collection
    {
        $cacheKey = "telegram_menu_children_{$parentId}_{$languageId}";

        return Cache::remember($cacheKey, 3600, function () use ($parentId, $languageId) {
            $query = TelegramMenuItem::where('parent_id', $parentId)
                ->active()
                ->ordered()
                ->with(['translations', 'menuItemImages.image']);

            if ($languageId) {
                $query->with([
                    'translations' => function ($q) use ($languageId) {
                        $q->where('language_id', $languageId);
                    },
                    'menuItemImages' => function ($q) use ($languageId) {
                        $q->where('language_id', $languageId)->orWhereNull('language_id');
                    }
                ]);
            }

            return $query->get();
        });
    }

    /**
     * 根据ID获取菜单项
     */
    public function findMenuItemById(int $id): ?TelegramMenuItem
    {
        return TelegramMenuItem::find($id);
    }

    /**
     * 根据key获取菜单项
     */
    public function findMenuItemByKey(string $key): ?TelegramMenuItem
    {
        return TelegramMenuItem::where('key', $key)->first();
    }

    /**
     * 构建Telegram键盘
     */
    public function buildTelegramKeyboard(Collection $menuItems, int $languageId = null): array
    {
        // 统一布局：每行 2 个按钮
        $keyboard = [];
        $row = [];

        foreach ($menuItems as $menuItem) {
            $translation = $menuItem->getTranslation($languageId);
            $title = $translation ? $translation->title : $menuItem->key;

            $button = ['text' => $title];

            switch ($menuItem->type) {
                case 'callback':
                    $button['callback_data'] = $menuItem->callback_data ?: $menuItem->key;
                    break;
                case 'url':
                    $button['url'] = $menuItem->url;
                    break;
                case 'submenu':
                    $button['callback_data'] = 'menu_' . $menuItem->key;
                    break;
                default:
                    $button['callback_data'] = $menuItem->callback_data ?: $menuItem->key;
                    break;
            }

            if (!isset($button['callback_data']) && !isset($button['url'])) {
                $button['callback_data'] = $menuItem->key;
            }

            $row[] = $button;
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if (count($row) > 0) {
            $keyboard[] = $row;
        }

        return $keyboard;
    }

    /**
     * 创建菜单项
     */
    public function createMenuItem(array $data): TelegramMenuItem
    {
        DB::beginTransaction();

        try {
            $menuItem = TelegramMenuItem::create([
                'key' => $data['key'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'],
                'callback_data' => $data['callback_data'] ?? null,
                'url' => $data['url'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // 创建翻译
            if (isset($data['translations'])) {
                foreach ($data['translations'] as $languageId => $translation) {
                    TelegramMenuTranslation::create([
                        'menu_item_id' => $menuItem->id,
                        'language_id' => (int)$languageId,
                        'title' => $translation['title'],
                        'description' => $translation['description'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // 清除缓存
            $this->clearMenuCache();

            return $menuItem;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create menu item: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新菜单项
     */
    public function updateMenuItem(int $id, array $data): TelegramMenuItem
    {
        DB::beginTransaction();

        try {
            $menuItem = TelegramMenuItem::findOrFail($id);

            $menuItem->update([
                'key' => $data['key'] ?? $menuItem->key,
                'parent_id' => $data['parent_id'] ?? $menuItem->parent_id,
                'type' => $data['type'] ?? $menuItem->type,
                'callback_data' => $data['callback_data'] ?? $menuItem->callback_data,
                'url' => $data['url'] ?? $menuItem->url,
                'is_active' => $data['is_active'] ?? $menuItem->is_active,
                'sort_order' => $data['sort_order'] ?? $menuItem->sort_order,
                'metadata' => $data['metadata'] ?? $menuItem->metadata,
            ]);

            // 更新翻译
            if (isset($data['translations'])) {
                foreach ($data['translations'] as $languageId => $translation) {
                    TelegramMenuTranslation::updateOrCreate(
                        [
                            'menu_item_id' => $menuItem->id,
                            'language_id' => (int)$languageId
                        ],
                        [
                            'title' => $translation['title'],
                            'description' => $translation['description'] ?? null,
                        ]
                    );
                }
            }

            DB::commit();

            // 清除缓存
            $this->clearMenuCache();

            return $menuItem->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update menu item: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除菜单项
     */
    public function deleteMenuItem(int $id): bool
    {
        DB::beginTransaction();

        try {
            $menuItem = TelegramMenuItem::findOrFail($id);

            // 删除子菜单项
            $this->deleteMenuItemRecursively($menuItem);

            DB::commit();

            // 清除缓存
            $this->clearMenuCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete menu item: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 递归删除菜单项及其子项
     */
    private function deleteMenuItemRecursively(TelegramMenuItem $menuItem): void
    {
        // 删除子菜单项
        foreach ($menuItem->allChildren as $child) {
            $this->deleteMenuItemRecursively($child);
        }

        // 删除翻译
        $menuItem->translations()->delete();

        // 删除图片关联
        $menuItem->menuItemImages()->delete();

        // 删除统计数据
        $menuItem->stats()->delete();

        // 删除菜单项
        $menuItem->delete();
    }

    /**
     * 更新菜单项排序
     */
    public function updateMenuOrder(array $orderData): bool
    {
        DB::beginTransaction();

        try {
            foreach ($orderData as $item) {
                TelegramMenuItem::where('id', $item['id'])
                    ->update([
                        'sort_order' => $item['sort_order'],
                        'parent_id' => $item['parent_id'] ?? null
                    ]);
            }

            DB::commit();

            // 清除缓存
            $this->clearMenuCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update menu order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 记录菜单使用统计
     */
    public function recordMenuStat(?int $menuItemId, int $userId, string $action, string $sessionId = null, array $metadata = []): void
    {
        try {
            // 检查用户是否存在，避免外键约束错误
            $userExists = \App\Models\TelegramUser::where('id', $userId)->exists();
            if (!$userExists) {
                return;
            }

            TelegramMenuStat::create([
                'menu_item_id' => $menuItemId,
                'user_id' => $userId,
                'action' => $action,
                'session_id' => $sessionId,
                'metadata' => $metadata,
                'action_time' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record menu stat: ' . $e->getMessage());
        }
    }

    /**
     * 获取菜单使用统计
     */
    public function getMenuStats(int $menuItemId = null, string $dateFrom = null, string $dateTo = null): array
    {
        $query = TelegramMenuStat::query();

        if ($menuItemId) {
            $query->where('menu_item_id', $menuItemId);
        }

        if ($dateFrom) {
            $query->where('clicked_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('clicked_at', '<=', $dateTo);
        }

        return [
            'total_items' => TelegramMenuItem::count(),
            'active_items' => TelegramMenuItem::active()->count(),
            'total_languages' => TelegramLanguage::active()->count(),
            'menu_clicks' => $query->count(),
        ];
    }

    /**
     * 清除菜单缓存
     */
    public function clearMenuCache(): void
    {
        $languages = TelegramLanguage::active()->get();

        // 清除根菜单缓存
        Cache::forget('telegram_menu_root_');
        foreach ($languages as $language) {
            Cache::forget("telegram_menu_root_{$language->id}");
        }

        // 清除其他相关缓存
        $menuItems = TelegramMenuItem::all();
        foreach ($menuItems as $menuItem) {
            Cache::forget("telegram_menu_item_{$menuItem->key}_");
            Cache::forget("telegram_menu_children_{$menuItem->id}_");

            foreach ($languages as $language) {
                Cache::forget("telegram_menu_item_{$menuItem->key}_{$language->id}");
                Cache::forget("telegram_menu_children_{$menuItem->id}_{$language->id}");
            }
        }
    }

    /**
     * 获取所有菜单项（扁平化列表，用于父级选择）
     *
     * @return Collection
     */
    public function getAllMenuItems(): Collection
    {
        return TelegramMenuItem::with(['translations'])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                $translation = $item->translations->first();
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'title' => $translation ? $translation->title : $item->key,
                    'type' => $item->type,
                    'parent_id' => $item->parent_id,
                    'is_active' => $item->is_active,
                ];
            });
    }

    /**
     * 获取完整的菜单树结构
     *
     * @param int|null $languageId
     * @param bool $includeInactive 是否包含非活动项
     * @return Collection
     */
    public function getMenuTree($languageId = null, bool $includeInactive = false): Collection
    {
        // 1. 一次性获取所有菜单项及其翻译
        $query = TelegramMenuItem::with(['translations' => function ($query) use ($languageId) {
            if ($languageId) {
                $query->where('language_id', $languageId);
            }
        }]);

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        $allItems = $query->orderBy('sort_order')->get();

        if ($allItems->isEmpty()) {
            return collect();
        }

        // 2. 在内存中高效构建树
        $nodes = [];
        $itemsById = $allItems->keyBy('id');

        // 第一遍：初始化所有节点数据
        foreach ($itemsById as $item) {
            $translation = $item->translations->first();
            // 如果没有翻译，使用 key 作为备用文本
            $text = $translation ? $translation->title : ($item->key ?? 'Untitled');

            $nodes[$item->id] = [
                'id' => $item->id,
                'key' => $item->key,
                'text' => $text,
                'type' => $item->type,
                'is_active' => $item->is_active,
                'parent_id' => $item->parent_id,
                'sort_order' => $item->sort_order,
                'callback_data' => $item->callback_data,
                'url' => $item->url,
                'metadata' => $item->metadata,
                'translations' => $item->translations->mapWithKeys(function ($t) {
                    return [$t->language_id => ['title' => $t->title, 'description' => $t->description]];
                })->all(),
                'children' => [],
            ];
        }

        // 第二遍：关联父子节点
        $tree = [];
        foreach ($nodes as $id => &$node) {
            if (isset($node['parent_id']) && isset($nodes[$node['parent_id']])) {
                // 将子节点添加到其父节点的 children 数组中
                $nodes[$node['parent_id']]['children'][] = &$node;
            } else {
                // 根节点
                $tree[] = &$node;
            }
        }
        unset($node); // 解除最后一个元素的引用

        // 递归地对所有子节点进行排序
        $sortChildrenRecursive = function (&$nodes) use (&$sortChildrenRecursive) {
            usort($nodes, function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });

            foreach ($nodes as &$node) {
                if (!empty($node['children'])) {
                    $sortChildrenRecursive($node['children']);
                }
            }
        };

        $sortChildrenRecursive($tree);

        return collect($tree);
    }
}
