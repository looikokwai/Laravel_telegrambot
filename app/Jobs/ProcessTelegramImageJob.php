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
use Intervention\Image\Facades\Image;

class ProcessTelegramImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imageId;
    protected $originalPath;
    protected $optimizationOptions;

    /**
     * Create a new job instance.
     */
    public function __construct(int $imageId, string $originalPath, array $optimizationOptions = [])
    {
        $this->imageId = $imageId;
        $this->originalPath = $originalPath;
        $this->optimizationOptions = array_merge([
            'quality' => 85,
            'max_width' => 1920,
            'max_height' => 1080,
            'create_thumbnails' => true,
            'thumbnail_sizes' => [
                'small' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300],
                'large' => ['width' => 600, 'height' => 600],
            ]
        ], $optimizationOptions);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $image = TelegramMenuImage::find($this->imageId);
            if (!$image) {
                Log::warning("Image with ID {$this->imageId} not found");
                return;
            }

            // 检查原始文件是否存在
            if (!Storage::disk('public')->exists($this->originalPath)) {
                Log::error("Original image file not found: {$this->originalPath}");
                $image->update(['status' => 'failed']);
                return;
            }

            // 更新状态为处理中
            $image->update(['status' => 'processing']);

            // 获取原始图片信息
            $originalFullPath = Storage::disk('public')->path($this->originalPath);
            $imageInfo = getimagesize($originalFullPath);
            
            if (!$imageInfo) {
                throw new \Exception('Invalid image file');
            }

            // 创建优化后的主图片
            $optimizedPath = $this->optimizeMainImage($originalFullPath);
            
            // 创建缩略图
            $thumbnails = [];
            if ($this->optimizationOptions['create_thumbnails']) {
                $thumbnails = $this->createThumbnails($originalFullPath);
            }

            // 计算文件大小
            $originalSize = Storage::disk('public')->size($this->originalPath);
            $optimizedSize = Storage::disk('public')->size($optimizedPath);

            // 更新数据库记录
            $image->update([
                'path' => $optimizedPath,
                'file_size' => $optimizedSize,
                'original_size' => $originalSize,
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'mime_type' => $imageInfo['mime'],
                'thumbnails' => json_encode($thumbnails),
                'optimization_stats' => json_encode([
                    'original_size' => $originalSize,
                    'optimized_size' => $optimizedSize,
                    'compression_ratio' => round((1 - $optimizedSize / $originalSize) * 100, 2),
                    'processed_at' => now()->toISOString(),
                ]),
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            // 删除原始临时文件（如果不同于优化后的文件）
            if ($this->originalPath !== $optimizedPath) {
                Storage::disk('public')->delete($this->originalPath);
            }

            Log::info("Image {$this->imageId} processed successfully");

        } catch (\Exception $e) {
            Log::error("Failed to process image {$this->imageId}: " . $e->getMessage());
            
            // 更新状态为失败
            if (isset($image)) {
                $image->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * 优化主图片
     */
    private function optimizeMainImage(string $originalPath): string
    {
        $pathInfo = pathinfo($this->originalPath);
        $optimizedFileName = $pathInfo['filename'] . '_optimized.' . $pathInfo['extension'];
        $optimizedPath = 'telegram/images/' . $optimizedFileName;
        $optimizedFullPath = Storage::disk('public')->path($optimizedPath);

        // 确保目录存在
        $directory = dirname($optimizedFullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // 使用 Intervention Image 进行优化
        $img = Image::make($originalPath);
        
        // 调整尺寸（如果超过最大尺寸）
        if ($img->width() > $this->optimizationOptions['max_width'] || 
            $img->height() > $this->optimizationOptions['max_height']) {
            $img->resize(
                $this->optimizationOptions['max_width'], 
                $this->optimizationOptions['max_height'], 
                function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );
        }

        // 保存优化后的图片
        $img->save($optimizedFullPath, $this->optimizationOptions['quality']);

        return $optimizedPath;
    }

    /**
     * 创建缩略图
     */
    private function createThumbnails(string $originalPath): array
    {
        $thumbnails = [];
        $pathInfo = pathinfo($this->originalPath);

        foreach ($this->optimizationOptions['thumbnail_sizes'] as $size => $dimensions) {
            try {
                $thumbnailFileName = $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
                $thumbnailPath = 'telegram/images/thumbnails/' . $thumbnailFileName;
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

                // 确保目录存在
                $directory = dirname($thumbnailFullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                // 创建缩略图
                $img = Image::make($originalPath);
                $img->fit($dimensions['width'], $dimensions['height']);
                $img->save($thumbnailFullPath, $this->optimizationOptions['quality']);

                $thumbnails[$size] = [
                    'path' => $thumbnailPath,
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'size' => Storage::disk('public')->size($thumbnailPath),
                ];

            } catch (\Exception $e) {
                Log::warning("Failed to create {$size} thumbnail for image {$this->imageId}: " . $e->getMessage());
            }
        }

        return $thumbnails;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessTelegramImageJob failed for image {$this->imageId}: " . $exception->getMessage());
        
        // 更新图片状态为失败
        $image = TelegramMenuImage::find($this->imageId);
        if ($image) {
            $image->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}