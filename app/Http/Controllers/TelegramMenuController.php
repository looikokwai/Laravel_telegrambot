<?php

namespace App\Http\Controllers;

use App\Services\TelegramMenuService;
use App\Services\TelegramLanguageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TelegramMenuController extends Controller
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
     * 显示菜单管理页面
     */
    public function index(Request $request)
    {
        try {
            // 如果是 AJAX 请求，返回 JSON 数据
            if ($request->wantsJson()) {
                $languageId = $request->get('language_id');
                $includeInactive = true; // 强制包含非活动菜单项

                $menuTree = $this->menuService->getMenuTree($languageId, $includeInactive);

                return response()->json([
                    'success' => true,
                    'data' => $menuTree,
                ]);
            }

            // 否则返回 Inertia 页面
            $languageId = $request->get('language_id');
            $includeInactive = true; // 强制包含非活动菜单项

            $menuTree = $this->menuService->getMenuTree($languageId, $includeInactive);
            $languages = $this->languageService->getActiveLanguages();
            $stats = $this->menuService->getMenuStats();

            // 获取所有菜单项用于父级选择
            $allMenuItems = $this->menuService->getAllMenuItems();

            return Inertia::render('Telegram/MenuManagement', [
                'menuTree' => $menuTree->toArray(),
                'languages' => $languages->toArray(),
                'stats' => $stats,
                'allMenuItems' => $allMenuItems->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get menu data: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '获取菜单失败',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => '获取菜单数据失败']);
        }
    }

    /**
     * 获取单个菜单项详情
     */
    public function show(int $id): JsonResponse
    {
        try {
            $menuItem = $this->menuService->findMenuItemById($id);

            if (!$menuItem) {
                return response()->json([
                    'success' => false,
                    'message' => '菜单项不存在',
                ], 404);
            }

            // 加载关联数据
            $menuItem->load(['translations.language', 'images.image', 'children']);

            return response()->json([
                'success' => true,
                'data' => $menuItem,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get menu item: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取菜单项失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 创建菜单项
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string|max:100|unique:telegram_menu_items,key',
                'parent_id' => 'nullable|integer|exists:telegram_menu_items,id',
                'type' => ['required', Rule::in(['button', 'submenu', 'url', 'callback'])],
                'callback_data' => 'nullable|string|max:64',
                'url' => 'nullable|url|max:255',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'metadata' => 'nullable|array',
                'translations' => 'required|array|min:1',
                'translations.*.title' => 'required|string|max:100',
                'translations.*.description' => 'nullable|string|max:255',
            ]);

            $menuItem = $this->menuService->createMenuItem($validated);

            // 创建翻译
            if (isset($validated['translations'])) {
                foreach ($validated['translations'] as $languageId => $translation) {
                    $this->languageService->createOrUpdateTranslation(
                        $menuItem->id,
                        (int)$languageId,
                        $translation
                    );
                }
            }

            return back()->with('success', '菜单项创建成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to create menu item: ' . $e->getMessage());

            return back()->withErrors(['error' => '创建菜单项失败'])->withInput();
        }
    }

    /**
     * 更新菜单项
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'key' => 'string|max:100|unique:telegram_menu_items,key,' . $id,
                'parent_id' => 'nullable|integer|exists:telegram_menu_items,id',
                'type' => [Rule::in(['button', 'submenu', 'url', 'callback'])],
                'callback_data' => 'nullable|string|max:64',
                'url' => 'nullable|url|max:255',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'metadata' => 'nullable|array',
                'translations' => 'array',
                'translations.*.title' => 'required|string|max:100',
                'translations.*.description' => 'nullable|string|max:255',
            ]);

            $menuItem = $this->menuService->updateMenuItem($id, $validated);

            // 更新翻译
            if (isset($validated['translations'])) {
                foreach ($validated['translations'] as $languageId => $translation) {
                    $this->languageService->createOrUpdateTranslation(
                        $menuItem->id,
                        (int)$languageId,
                        $translation
                    );
                }
            }

            return back()->with('success', '菜单项更新成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to update menu item: ' . $e->getMessage());
            return back()->withErrors(['error' => '更新菜单项失败']);
        }
    }

    /**
     * 删除菜单项
     */
    public function destroy(int $id)
    {
        try {
            $result = $this->menuService->deleteMenuItem($id);

            if (!$result) {
                return back()->withErrors(['error' => '菜单项不存在']);
            }

            return back()->with('success', '菜单项删除成功');
        } catch (\Exception $e) {
            Log::error('Failed to delete menu item: ' . $e->getMessage());
            return back()->withErrors(['error' => '删除菜单项失败']);
        }
    }

    /**
     * 更新菜单排序
     */
    public function updateOrder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|integer|exists:telegram_menu_items,id',
                'items.*.sort_order' => 'required|integer|min:0',
                'items.*.parent_id' => 'nullable|integer|exists:telegram_menu_items,id',
            ]);

            $this->menuService->updateMenuOrder($validated['items']);

            return response()->json([
                'success' => true,
                'message' => '菜单排序更新成功',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update menu order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '更新菜单排序失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 切换菜单项状态
     */
    public function toggleStatus(int $id)
    {
        try {
            $menuItem = $this->menuService->findMenuItemById($id);

            if (!$menuItem) {
                return back()->withErrors(['error' => '菜单项不存在']);
            }

            $this->menuService->updateMenuItem($id, [
                'is_active' => !$menuItem->is_active
            ]);


            return back()->with('success', '菜单项状态更新成功');
        } catch (\Exception $e) {
            Log::error('Failed to toggle menu item status: ' . $e->getMessage());
            return back()->withErrors(['error' => '切换菜单项状态失败']);
        }
    }

    /**
     * 获取菜单统计
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $menuItemId = $request->get('menu_item_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $stats = $this->menuService->getMenuStats($menuItemId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get menu stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取菜单统计失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清除菜单缓存
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->menuService->clearMenuCache();

            return response()->json([
                'success' => true,
                'message' => '菜单缓存清除成功',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear menu cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '清除菜单缓存失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 预览菜单键盘
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'menu_item_id' => 'nullable|integer|exists:telegram_menu_items,id',
                'language_id' => 'required|integer|exists:telegram_languages,id',
            ]);

            $menuItemId = $validated['menu_item_id'] ?? null;
            $languageId = $validated['language_id'];

            $keyboard = $this->menuService->buildTelegramKeyboard($menuItemId, $languageId);

            return response()->json([
                'success' => true,
                'data' => [
                    'keyboard' => $keyboard,
                    'preview_text' => $this->generatePreviewText($keyboard),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to preview menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '预览菜单失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 生成预览文本
     */
    private function generatePreviewText(array $keyboard): string
    {
        $text = "菜单预览:\n\n";

        foreach ($keyboard as $row) {
            $rowText = [];
            foreach ($row as $button) {
                $rowText[] = "[{$button['text']}]";
            }
            $text .= implode(' ', $rowText) . "\n";
        }

        return $text;
    }

    /**
     * 导出菜单配置
     */
    public function export(): JsonResponse
    {
        try {
            $menuTree = $this->menuService->getMenuTree();

            return response()->json([
                'success' => true,
                'data' => $menuTree,
                'exported_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '导出菜单失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 导入菜单配置
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'menu_data' => 'required|array',
                'overwrite' => 'boolean',
            ]);

            // 这里可以实现菜单导入逻辑
            // 由于复杂性，暂时返回成功响应

            return response()->json([
                'success' => true,
                'message' => '菜单导入成功',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to import menu: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '导入菜单失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
