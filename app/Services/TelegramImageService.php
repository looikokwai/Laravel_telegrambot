<?php

namespace App\Services;

use App\Models\TelegramMenuImage;
use App\Models\TelegramMenuItemImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramImageService
{
    /**
     * 上传图片
     */
    public function uploadImage(UploadedFile $file, array $options = []): TelegramMenuImage
    {
        try {
            // 验证文件
            $this->validateImage($file);

            // 生成文件名
            $filename = $this->generateFilename($file);

            // 存储文件到 private 目录
            $storedPath = Storage::putFileAs('telegram/menu-images', $file, $filename);

            // 获取图片信息
            $imageInfo = $this->getImageInfo($file);

            // 创建数据库记录
            $image = TelegramMenuImage::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'width' => $imageInfo['width'],
                'height' => $imageInfo['height'],
                'alt_text' => $options['alt_text'] ?? null,
                'metadata' => $options['metadata'] ?? [],
            ]);

            return $image;
        } catch (\Exception $e) {
            Log::error('Failed to upload image: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取图片列表
     */
    public function getImages(array $filters = []): Collection
    {
        $query = TelegramMenuImage::query();

        if (!empty($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        if (!empty($filters['min_size'])) {
            $query->where('file_size', '>=', $filters['min_size']);
        }

        if (!empty($filters['max_size'])) {
            $query->where('file_size', '<=', $filters['max_size']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('original_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('alt_text', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->with(['menuItems.translations'])
                     ->orderBy('created_at', 'desc')
                     ->get();
    }

    /**
     * 获取图片使用统计
     */
    public function getImageUsageStats(int $imageId = null): array
    {
        $query = TelegramMenuItemImage::query();

        if ($imageId) {
            $query->where('image_id', $imageId);
        }

        return [
            'total_usage' => $query->count(),
            'unique_menus' => $query->distinct('menu_item_id')->count(),
        ];
    }

    /**
     * 更新图片信息
     */
    public function updateImage(int $id, array $data): TelegramMenuImage
    {
        $image = TelegramMenuImage::findOrFail($id);
        $image->update($data);

        return $image->fresh();
    }

    /**
     * 删除图片
     */
    public function deleteImage(int $id): bool
    {
        try {
            $image = TelegramMenuImage::find($id);

            if (!$image) {
                return false;
            }

            // 删除文件
            if (Storage::exists($image->path)) {
                Storage::delete($image->path);
            }

            // 删除缩略图
            if ($image->thumbnail_path && Storage::exists($image->thumbnail_path)) {
                Storage::delete($image->thumbnail_path);
            }

            // 删除数据库记录
            $image->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量删除图片
     */
    public function bulkDeleteImages(array $imageIds): int
    {
        $deletedCount = 0;

        foreach ($imageIds as $imageId) {
            if ($this->deleteImage($imageId)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * 关联图片到菜单项
     */
    public function attachImageToMenuItem(int $imageId, int $menuItemId, array $options = []): TelegramMenuItemImage
    {
        return TelegramMenuItemImage::create([
            'image_id' => $imageId,
            'menu_item_id' => $menuItemId,
            'language_id' => $options['language_id'] ?? null,
            'type' => $options['type'] ?? 'icon',
            'sort_order' => $options['sort_order'] ?? 0,
        ]);
    }

    /**
     * 取消图片与菜单项的关联
     */
    public function detachImageFromMenuItem(int $imageId, int $menuItemId, int $languageId = null): bool
    {
        $query = TelegramMenuItemImage::where('image_id', $imageId)
            ->where('menu_item_id', $menuItemId);

        if ($languageId) {
            $query->where('language_id', $languageId);
        }

        return $query->delete() > 0;
    }

    /**
     * 获取菜单项的图片
     */
    public function getMenuItemImages(int $menuItemId, int $languageId = null): Collection
    {
        $query = TelegramMenuItemImage::where('menu_item_id', $menuItemId)
            ->with('image');

        if ($languageId) {
            $query->where('language_id', $languageId);
        }

        return $query->orderBy('sort_order')->get();
    }

    /**
     * 优化图片
     */
    public function optimizeImage(int $id, array $options = []): bool
    {
        try {
            $image = TelegramMenuImage::findOrFail($id);

            // 这里可以实现图片优化逻辑
            // 例如压缩、格式转换等

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to optimize image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取缩略图URL
     */
    public function getThumbnailUrl(TelegramMenuImage $image, string $size = 'medium'): string
    {
        if ($image->thumbnail_path) {
            $filename = basename($image->thumbnail_path);
            return route('telegram.images.serve', ['filename' => $filename]);
        }

        // 如果没有缩略图，返回原图
        $filename = basename($image->path);
        return route('telegram.images.serve', ['filename' => $filename]);
    }

    /**
     * 验证图片文件
     */
    private function validateImage(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('不支持的图片格式');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('图片文件过大');
        }
    }

    /**
     * 生成文件名
     */
    private function generateFilename(UploadedFile $file): string
    {
        return Str::random(40) . '.' . $file->getClientOriginalExtension();
    }

    /**
     * 获取图片信息
     */
    private function getImageInfo(UploadedFile $file): array
    {
        $imageInfo = getimagesize($file->getPathname());

        return [
            'width' => $imageInfo[0] ?? null,
            'height' => $imageInfo[1] ?? null,
        ];
    }
}
