<?php

namespace App\Http\Controllers;

use App\Services\TelegramImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TelegramImageController extends Controller
{
    protected TelegramImageService $imageService;

    public function __construct(TelegramImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * 显示图片管理页面
     */
    public function index(Request $request)
    {
        try {
            // 如果是 AJAX 请求，返回 JSON 数据
            if ($request->wantsJson()) {
                $validated = $request->validate([
                    'mime_type' => 'nullable|string',
                    'min_size' => 'nullable|integer|min:0',
                    'max_size' => 'nullable|integer|min:0',
                    'search' => 'nullable|string|max:255',
                    'page' => 'nullable|integer|min:1',
                    'per_page' => 'nullable|integer|min:1|max:100',
                ]);

                $filters = [
                    'mime_type' => $validated['mime_type'] ?? null,
                    'min_size' => $validated['min_size'] ?? null,
                    'max_size' => $validated['max_size'] ?? null,
                    'search' => $validated['search'] ?? null,
                ];

                $images = $this->imageService->getImages($filters);

                // 分页处理
                $perPage = $validated['per_page'] ?? 20;
                $page = $validated['page'] ?? 1;
                $total = $images->count();
                $paginatedImages = $images->forPage($page, $perPage)->values();

                return response()->json([
                    'success' => true,
                    'data' => $paginatedImages,
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => ceil($total / $perPage),
                    ],
                ]);
            }

            // 否则返回 Inertia 页面
            $images = $this->imageService->getImages([]);
            $menuItems = \App\Models\TelegramMenuItem::with('translations')->get();
            $languages = \App\Models\TelegramLanguage::ordered()->get(['id','code','name','native_name']);

            $stats = [
                'total_images' => $images->count(),
                'total_size' => $images->sum('file_size'),
                'image_types' => $images->groupBy('mime_type')->map->count(),
                'associated_images' => \App\Models\TelegramMenuImage::whereHas('menuItemImages')->count(),
            ];

            return Inertia::render('Telegram/ImageManagement', [
                'images' => $images->take(20), // 只显示前20个
                'menuItems' => $menuItems,
                'stats' => $stats,
                'languages' => $languages,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to get images: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取图片列表失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取单个图片详情
     */
    public function show(int $id): JsonResponse
    {
        try {
            $image = \App\Models\TelegramMenuImage::with('menuItems.menuItem')->find($id);

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => '图片不存在',
                ], 404);
            }

            // 获取使用统计
            $usageStats = $this->imageService->getImageUsageStats($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'image' => $image,
                    'usage_stats' => $usageStats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取图片详情失败',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 上传图片
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB
                'alt_text' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
                'generate_thumbnails' => 'boolean',
            ]);

            $options = [
                'alt_text' => $validated['alt_text'] ?? null,
                'metadata' => $validated['metadata'] ?? [],
                'generate_thumbnails' => $validated['generate_thumbnails'] ?? true,
            ];

            $image = $this->imageService->uploadImage($request->file('image'), $options);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '图片上传成功',
                    'data' => $image,
                ], 201);
            }
            return back()->with('success', '图片上传成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to store image: ' . $e->getMessage());
            return back()->withErrors(['error' => '图片上传失败'])->withInput();
        }
    }

    /**
     * 更新图片信息
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'alt_text' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            $this->imageService->updateImage($id, $validated);

            return back()->with('success', '图片信息更新成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Failed to update image {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => '图片信息更新失败'])->withInput();
        }
    }

    /**
     * 删除图片
     */
    public function destroy(int $id)
    {
        try {
            $this->imageService->deleteImage($id);

            return back()->with('success', '图片删除成功');
        } catch (\Exception $e) {
            Log::error("Failed to delete image {$id}: " . $e->getMessage());
            return back()->withErrors(['error' => '图片删除失败']);
        }
    }

    /**
     * 将图片关联到菜单项
     */
    public function attachToMenuItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'image_id' => 'required|integer|exists:telegram_menu_images,id',
                'menu_item_id' => 'required|integer|exists:telegram_menu_items,id',
                'language_id' => 'nullable|integer|exists:telegram_languages,id',
                'type' => 'nullable|string|in:icon,banner,thumbnail',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $options = [
                'language_id' => $validated['language_id'] ?? null,
                'type' => $validated['type'] ?? 'icon',
                'sort_order' => $validated['sort_order'] ?? 0,
            ];

            $attachment = $this->imageService->attachImageToMenuItem(
                $validated['image_id'],
                $validated['menu_item_id'],
                $options
            );

            // 如果是 AJAX/JSON 请求，返回 JSON 响应
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '图片关联成功',
                    'data' => $attachment,
                ]);
            }

            // 对于 Inertia 请求，返回重定向
            return back()->with('success', '图片关联成功');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to attach image to menu item: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '图片关联失败',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['error' => '图片关联失败']);
        }
    }

    /**
     * 从菜单项中移除图片关联
     */
    public function detachFromMenuItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'image_id' => 'required|integer|exists:telegram_menu_images,id',
                'menu_item_id' => 'required|integer|exists:telegram_menu_items,id',
                'language_id' => 'nullable|integer|exists:telegram_languages,id',
            ]);

            $success = $this->imageService->detachImageFromMenuItem(
                $validated['image_id'],
                $validated['menu_item_id'],
                $validated['language_id'] ?? null
            );

            if ($success) {
                // 如果是 AJAX/JSON 请求，返回 JSON 响应
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => '图片关联移除成功',
                    ]);
                }
                // 对于 Inertia 请求，返回重定向
                return back()->with('success', '图片关联移除成功');
            } else {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '未找到对应的图片关联',
                    ], 404);
                }
                return back()->withErrors(['error' => '未找到对应的图片关联']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to detach image from menu item: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '图片关联移除失败',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['error' => '图片关联移除失败']);
        }
    }

    /**
     * 批量删除图片
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'image_ids' => 'required|array|min:1',
                'image_ids.*' => 'integer|exists:telegram_menu_images,id',
            ]);

            $deletedCount = 0;
            $errors = [];

            foreach ($validated['image_ids'] as $imageId) {
                try {
                    if ($this->imageService->deleteImage($imageId)) {
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "图片 ID {$imageId}: " . $e->getMessage();
                }
            }

            $response = [
                'success' => $deletedCount > 0,
                'message' => "成功删除 {$deletedCount} 张图片",
                'deleted_count' => $deletedCount,
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            // 如果是 AJAX/JSON 请求，返回 JSON 响应
            if ($request->wantsJson()) {
                return response()->json($response);
            }

            // 对于 Inertia 请求，返回重定向
            if ($deletedCount > 0) {
                $message = "成功删除 {$deletedCount} 张图片";
                if (!empty($errors)) {
                    $message .= "，但有部分图片删除失败";
                }
                return back()->with('success', $message);
            } else {
                return back()->withErrors(['error' => '批量删除失败']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete images: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '批量删除失败',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['error' => '批量删除失败']);
        }
    }

    /**
     * 优化图片
     */
    public function optimize(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'quality' => 'nullable|integer|min:1|max:100',
                'max_width' => 'nullable|integer|min:1',
                'max_height' => 'nullable|integer|min:1',
                'format' => 'nullable|string|in:jpeg,png,webp',
            ]);

            $options = [
                'quality' => $validated['quality'] ?? 85,
                'max_width' => $validated['max_width'] ?? null,
                'max_height' => $validated['max_height'] ?? null,
                'format' => $validated['format'] ?? null,
            ];

            $success = $this->imageService->optimizeImage($id, $options);

            if ($success) {
                // 如果是 AJAX/JSON 请求，返回 JSON 响应
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => '图片优化成功',
                    ]);
                }
                // 对于 Inertia 请求，返回重定向
                return back()->with('success', '图片优化成功');
            } else {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '图片优化失败',
                    ], 500);
                }
                return back()->withErrors(['error' => '图片优化失败']);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error("Failed to optimize image {$id}: " . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '图片优化失败',
                    'error' => $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['error' => '图片优化失败']);
        }
    }

    /**
     * 提供图片文件访问
     */
    public function serveImage(Request $request, string $filename)
    {
        try {
            // 查找图片记录
            $image = \App\Models\TelegramMenuImage::where('filename', $filename)->first();

            if (!$image) {
                abort(404, '图片不存在');
            }

            // 检查文件是否存在
            if (!Storage::exists($image->path)) {
                abort(404, '图片文件不存在');
            }

            // 获取文件内容
            $file = Storage::get($image->path);

            // 设置响应头
            $headers = [
                'Content-Type' => $image->mime_type,
                'Content-Length' => strlen($file),
                'Cache-Control' => 'public, max-age=31536000', // 缓存一年
                'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            ];

            // 如果请求包含 If-Modified-Since 头，检查是否需要返回 304
            $lastModified = $image->updated_at->format('D, d M Y H:i:s') . ' GMT';
            $headers['Last-Modified'] = $lastModified;

            if ($request->header('If-Modified-Since') === $lastModified) {
                return response('', 304, $headers);
            }

            return response($file, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Failed to serve image: ' . $e->getMessage());
            abort(500, '服务器错误');
        }
    }
}
