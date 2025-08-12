<?php

namespace App\Http\Controllers;

use App\Services\TelegramLanguageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TelegramLanguageController extends Controller
{
    protected TelegramLanguageService $languageService;

    public function __construct(TelegramLanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * 显示语言管理页面
     */
    public function index(Request $request)
    {
        try {
            // 如果是 AJAX 请求，返回 JSON 数据
            if ($request->wantsJson()) {
                $validated = $request->validate([
                    'active_only' => 'boolean',
                    'with_stats' => 'boolean',
                ]);

                $activeOnly = $validated['active_only'] ?? false;
                $withStats = $validated['with_stats'] ?? false;

                if ($activeOnly) {
                    $languages = $this->languageService->getActiveLanguages();
                } else {
                    $languages = \App\Models\TelegramLanguage::ordered()->get();
                }

                if ($withStats) {
                    $languages = $languages->map(function ($language) {
                        $stats = $this->languageService->getLanguageStats($language->id);
                        $language->stats = $stats;
                        return $language;
                    });
                }

                return response()->json([
                    'success' => true,
                    'data' => $languages,
                ]);
            }

            // 否则返回 Inertia 页面
            $languages = \App\Models\TelegramLanguage::with(['languageImages.image'])->ordered()->get();

            // 为每个语言添加selection_image属性
            $languages = $languages->map(function ($language) {
                $selectionImage = $language->languageImages->where('type', 'selection')->first();
                $language->selection_image = $selectionImage ? $selectionImage->image : null;
                return $language;
            });
            $stats = [
                'total_languages' => $languages->count(),
                'active_languages' => $languages->where('is_active', true)->count(),
                'total_translations' => 0, // 可以后续实现
            ];

            // 获取可用图片列表
            $availableImages = \App\Models\TelegramMenuImage::select('id', 'filename', 'width', 'height', 'path')
                ->orderBy('filename')
                ->get();

            return Inertia::render('Telegram/LanguageManagement', [
                'languages' => $languages,
                'stats' => $stats,
                'availableImages' => $availableImages,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get languages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取语言列表失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取单个语言详情
     */
    public function show(int $id): JsonResponse
    {
        try {
            $language = \App\Models\TelegramLanguage::with(['menuTranslations.menuItem'])->find($id);

            if (!$language) {
                return response()->json([
                    'success' => false,
                    'message' => '语言不存在',
                ], 404);
            }

            // 获取语言统计信息
            $stats = $this->languageService->getLanguageStats($id);
            $language->stats = $stats;

            return response()->json([
                'success' => true,
                'data' => $language,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get language: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取语言详情失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 创建语言
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'code' => 'required|string|max:10|unique:telegram_languages,code',
                'native_name' => 'nullable|string|max:100',
                'flag_emoji' => 'nullable|string|max:10',
                'is_rtl' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'selection_title' => 'nullable|string|max:255',
                'selection_prompt' => 'nullable|string|max:1000',
                'selection_image_id' => 'nullable|integer|exists:telegram_menu_images,id',
                'back_label' => 'nullable|string|max:50',
            ]);

            $language = $this->languageService->createLanguage($validated);

            // 处理图片关联
            if (!empty($validated['selection_image_id'])) {
                $language->languageImages()->create([
                    'image_id' => $validated['selection_image_id'],
                    'type' => 'selection',
                    'sort_order' => 0,
                ]);
            }

            return back()->with('success', '语言创建成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to create language: ' . $e->getMessage());
            return back()->withErrors(['error' => '创建语言失败'])->withInput();
        }
    }

    /**
     * 更新语言
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'code' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('telegram_languages', 'code')->ignore($id)],
                'native_name' => 'nullable|string|max:100',
                'flag_emoji' => 'nullable|string|max:10',
                'is_rtl' => 'boolean',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'selection_title' => 'nullable|string|max:255',
                'selection_prompt' => 'nullable|string|max:1000',
                'selection_image_id' => 'nullable|integer|exists:telegram_menu_images,id',
                'back_label' => 'nullable|string|max:50',
            ]);

            $language = $this->languageService->updateLanguage($id, $validated);

            // 处理图片关联
            $language->languageImages()->where('type', 'selection')->delete();
            if (!empty($validated['selection_image_id'])) {
                $language->languageImages()->create([
                    'image_id' => $validated['selection_image_id'],
                    'type' => 'selection',
                    'sort_order' => 0,
                ]);
            }

            return back()->with('success', '语言更新成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Failed to update language {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => '更新语言失败'])->withInput();
        }
    }

    /**
     * 删除语言
     */
    public function destroy(int $id)
    {
        try {
            $this->languageService->deleteLanguage($id);

            return back()->with('success', '语言删除成功');
        } catch (\Exception $e) {
            Log::error("Failed to delete language {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => '删除语言失败']);
        }
    }

    /**
     * 设置默认语言
     */
    public function setDefault(int $id)
    {
        try {
            $this->languageService->setDefaultLanguage($id);
            return back()->with('success', 'Default language set successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggleStatus(int $id)
    {
        try {
            $this->languageService->toggleLanguageStatus($id);

            return back()->with('success', '语言状态更新成功');
        } catch (\Exception $e) {
            Log::error("Failed to toggle language status {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => '切换语言状态失败']);
        }
    }

    /**
     * 更新语言排序
     */
    public function updateOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'languages' => 'required|array',
                'languages.*.id' => 'required|integer|exists:telegram_languages,id',
                'languages.*.sort_order' => 'required|integer|min:0',
            ]);

            $this->languageService->updateLanguageOrder($validated['languages']);

            return back()->with('success', '语言排序更新成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update language order: ' . $e->getMessage());
            return back()->withErrors(['error' => '更新语言排序失败']);
        }
    }

    /**
     * 批量创建翻译
     */
    public function batchCreateTranslations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'translations' => 'required|array',
                'translations.*.menu_item_id' => 'required|integer|exists:telegram_menu_items,id',
                'translations.*.language_id' => 'required|integer|exists:telegram_languages,id',
                'translations.*.title' => 'required|string|max:255',
                'translations.*.description' => 'nullable|string',
            ]);

            $created = $this->languageService->batchCreateTranslations($validated['translations']);

            return response()->json([
                'success' => true,
                'message' => "成功创建 {$created} 个翻译",
                'created_count' => $created,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to batch create translations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '批量创建翻译失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 导出语言数据
     */
    public function exportLanguageData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'language_id' => 'nullable|integer|exists:telegram_languages,id',
            ]);

            $data = $this->languageService->exportLanguageData($validated['language_id'] ?? null);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => '语言数据导出成功',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to export language data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '导出语言数据失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 导入语言数据
     */
    public function importLanguageData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'data' => 'required|array',
            ]);

            $this->languageService->importLanguageData($validated['data']);

            return response()->json([
                'success' => true,
                'message' => '语言数据导入成功',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to import language data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '导入语言数据失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 清除缓存
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->languageService->clearLanguageCache();

            return response()->json([
                'success' => true,
                'message' => '语言缓存清除成功',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear language cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '清除语言缓存失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
