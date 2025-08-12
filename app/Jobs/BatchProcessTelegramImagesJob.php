<?php

namespace App\Jobs;

use App\Models\TelegramMenuImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BatchProcessTelegramImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $operation;
    protected $imageIds;
    protected $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $operation, array $imageIds, array $options = [])
    {
        $this->operation = $operation;
        $this->imageIds = $imageIds;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            switch ($this->operation) {
                case 'delete':
                    $this->batchDelete();
                    break;
                case 'optimize':
                    $this->batchOptimize();
                    break;
                case 'regenerate_thumbnails':
                    $this->batchRegenerateThumbnails();
                    break;
                case 'update_status':
                    $this->batchUpdateStatus();
                    break;
                case 'cleanup_orphaned':
                    $this->cleanupOrphanedImages();
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown operation: {$this->operation}");
            }

            Log::info("Batch operation '{$this->operation}' completed for " . count($this->imageIds) . " images");

        } catch (\Exception $e) {
            Log::error("Batch operation '{$this->operation}' failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 批量删除图片
     */
    private function batchDelete(): void
    {
        $images = TelegramMenuImage::whereIn('id', $this->imageIds)->get();
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($images as $image) {
            try {
                // 删除物理文件
                if ($image->path && Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }

                // 删除缩略图
                if ($image->thumbnails) {
                    $thumbnails = json_decode($image->thumbnails, true);
                    foreach ($thumbnails as $thumbnail) {
                        if (isset($thumbnail['path']) && Storage::disk('public')->exists($thumbnail['path'])) {
                            Storage::disk('public')->delete($thumbnail['path']);
                        }
                    }
                }

                // 删除数据库记录
                $image->delete();
                $deletedCount++;

            } catch (\Exception $e) {
                Log::error("Failed to delete image {$image->id}: " . $e->getMessage());
                $failedCount++;
            }
        }

        Log::info("Batch delete completed: {$deletedCount} deleted, {$failedCount} failed");
    }

    /**
     * 批量优化图片
     */
    private function batchOptimize(): void
    {
        $images = TelegramMenuImage::whereIn('id', $this->imageIds)
            ->whereIn('status', ['pending', 'failed'])
            ->get();

        foreach ($images as $image) {
            try {
                // 分发单个图片优化任务
                ProcessTelegramImageJob::dispatch(
                    $image->id,
                    $image->path,
                    $this->options
                );

            } catch (\Exception $e) {
                Log::error("Failed to dispatch optimization job for image {$image->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * 批量重新生成缩略图
     */
    private function batchRegenerateThumbnails(): void
    {
        $images = TelegramMenuImage::whereIn('id', $this->imageIds)
            ->where('status', 'completed')
            ->get();

        foreach ($images as $image) {
            try {
                if (!$image->path || !Storage::disk('public')->exists($image->path)) {
                    Log::warning("Image file not found for regenerating thumbnails: {$image->id}");
                    continue;
                }

                // 删除现有缩略图
                if ($image->thumbnails) {
                    $thumbnails = json_decode($image->thumbnails, true);
                    foreach ($thumbnails as $thumbnail) {
                        if (isset($thumbnail['path']) && Storage::disk('public')->exists($thumbnail['path'])) {
                            Storage::disk('public')->delete($thumbnail['path']);
                        }
                    }
                }

                // 重新生成缩略图
                $originalPath = Storage::disk('public')->path($image->path);
                $thumbnails = $this->createThumbnails($originalPath, $image->id);

                // 更新数据库
                $image->update([
                    'thumbnails' => json_encode($thumbnails),
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to regenerate thumbnails for image {$image->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * 批量更新状态
     */
    private function batchUpdateStatus(): void
    {
        $status = $this->options['status'] ?? 'active';
        
        TelegramMenuImage::whereIn('id', $this->imageIds)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    /**
     * 清理孤立的图片文件
     */
    private function cleanupOrphanedImages(): void
    {
        $imageDirectory = 'telegram/images';
        $thumbnailDirectory = 'telegram/images/thumbnails';
        
        // 获取数据库中所有图片路径
        $dbImagePaths = TelegramMenuImage::pluck('path')->toArray();
        $dbThumbnailPaths = [];
        
        // 收集所有缩略图路径
        TelegramMenuImage::whereNotNull('thumbnails')->chunk(100, function ($images) use (&$dbThumbnailPaths) {
            foreach ($images as $image) {
                $thumbnails = json_decode($image->thumbnails, true);
                if ($thumbnails) {
                    foreach ($thumbnails as $thumbnail) {
                        if (isset($thumbnail['path'])) {
                            $dbThumbnailPaths[] = $thumbnail['path'];
                        }
                    }
                }
            }
        });

        $deletedFiles = 0;
        $deletedSize = 0;

        // 清理主图片目录
        if (Storage::disk('public')->exists($imageDirectory)) {
            $files = Storage::disk('public')->files($imageDirectory);
            foreach ($files as $file) {
                if (!in_array($file, $dbImagePaths)) {
                    $size = Storage::disk('public')->size($file);
                    Storage::disk('public')->delete($file);
                    $deletedFiles++;
                    $deletedSize += $size;
                }
            }
        }

        // 清理缩略图目录
        if (Storage::disk('public')->exists($thumbnailDirectory)) {
            $files = Storage::disk('public')->allFiles($thumbnailDirectory);
            foreach ($files as $file) {
                if (!in_array($file, $dbThumbnailPaths)) {
                    $size = Storage::disk('public')->size($file);
                    Storage::disk('public')->delete($file);
                    $deletedFiles++;
                    $deletedSize += $size;
                }
            }
        }

        Log::info("Cleanup completed: {$deletedFiles} orphaned files deleted, " . 
                 round($deletedSize / 1024 / 1024, 2) . " MB freed");
    }

    /**
     * 创建缩略图（复用逻辑）
     */
    private function createThumbnails(string $originalPath, int $imageId): array
    {
        $thumbnails = [];
        $thumbnailSizes = $this->options['thumbnail_sizes'] ?? [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 600],
        ];
        $quality = $this->options['quality'] ?? 85;

        foreach ($thumbnailSizes as $size => $dimensions) {
            try {
                $pathInfo = pathinfo($originalPath);
                $thumbnailFileName = $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
                $thumbnailPath = 'telegram/images/thumbnails/' . $thumbnailFileName;
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

                // 确保目录存在
                $directory = dirname($thumbnailFullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // 创建缩略图
                $img = \Intervention\Image\Facades\Image::make($originalPath);
                $img->fit($dimensions['width'], $dimensions['height']);
                $img->save($thumbnailFullPath, $quality);

                $thumbnails[$size] = [
                    'path' => $thumbnailPath,
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'size' => Storage::disk('public')->size($thumbnailPath),
                ];

            } catch (\Exception $e) {
                Log::warning("Failed to create {$size} thumbnail for image {$imageId}: " . $e->getMessage());
            }
        }

        return $thumbnails;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("BatchProcessTelegramImagesJob failed for operation '{$this->operation}': " . $exception->getMessage());
    }
}