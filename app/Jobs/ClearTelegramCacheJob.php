<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\TelegramMenuItem;
use App\Models\TelegramMenuImage;
use App\Models\TelegramLanguage;

class ClearTelegramCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cacheType;
    protected $specificKeys;
    protected $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $cacheType = 'all', array $specificKeys = [], array $options = [])
    {
        $this->cacheType = $cacheType;
        $this->specificKeys = $specificKeys;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            switch ($this->cacheType) {
                case 'menu':
                    $this->clearMenuCache();
                    break;
                case 'images':
                    $this->clearImageCache();
                    break;
                case 'languages':
                    $this->clearLanguageCache();
                    break;
                case 'stats':
                    $this->clearStatsCache();
                    break;
                case 'specific':
                    $this->clearSpecificKeys();
                    break;
                case 'orphaned':
                    $this->cleanupOrphanedData();
                    break;
                case 'all':
                default:
                    $this->clearAllTelegramCache();
                    break;
            }

            Log::info("Telegram cache cleared successfully: {$this->cacheType}");

        } catch (\Exception $e) {
            Log::error("Failed to clear Telegram cache ({$this->cacheType}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 清理菜单相关缓存
     */
    private function clearMenuCache(): void
    {
        $patterns = [
            'telegram_menu_*',
            'telegram_menu_tree_*',
            'telegram_menu_structure_*',
            'telegram_menu_translations_*',
            'telegram_dynamic_menu_*',
        ];

        $this->clearCacheByPatterns($patterns);

        // 重新构建菜单缓存
        if ($this->options['rebuild'] ?? false) {
            $this->rebuildMenuCache();
        }
    }

    /**
     * 清理图片相关缓存
     */
    private function clearImageCache(): void
    {
        $patterns = [
            'telegram_image_*',
            'telegram_menu_images_*',
            'telegram_image_stats_*',
            'telegram_image_thumbnails_*',
        ];

        $this->clearCacheByPatterns($patterns);

        // 清理孤立的图片文件
        if ($this->options['cleanup_files'] ?? false) {
            $this->cleanupOrphanedImageFiles();
        }
    }

    /**
     * 清理语言相关缓存
     */
    private function clearLanguageCache(): void
    {
        $patterns = [
            'telegram_language_*',
            'telegram_translations_*',
            'telegram_locale_*',
            'telegram_menu_translations_*',
        ];

        $this->clearCacheByPatterns($patterns);

        // 重新构建语言缓存
        if ($this->options['rebuild'] ?? false) {
            $this->rebuildLanguageCache();
        }
    }

    /**
     * 清理统计相关缓存
     */
    private function clearStatsCache(): void
    {
        $patterns = [
            'telegram_menu_stats_*',
            'telegram_image_stats_*',
            'telegram_language_stats_*',
            'telegram_stats_overview_*',
            'telegram_popular_*',
        ];

        $this->clearCacheByPatterns($patterns);
    }

    /**
     * 清理指定的缓存键
     */
    private function clearSpecificKeys(): void
    {
        foreach ($this->specificKeys as $key) {
            Cache::forget($key);
            Log::debug("Cleared specific cache key: {$key}");
        }
    }

    /**
     * 清理所有 Telegram 相关缓存
     */
    private function clearAllTelegramCache(): void
    {
        $this->clearMenuCache();
        $this->clearImageCache();
        $this->clearLanguageCache();
        $this->clearStatsCache();

        // 清理其他通用缓存
        $otherPatterns = [
            'telegram_bot_*',
            'telegram_webhook_*',
            'telegram_user_*',
        ];

        $this->clearCacheByPatterns($otherPatterns);
    }

    /**
     * 根据模式清理缓存
     */
    private function clearCacheByPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern) {
            // 由于 Laravel Cache 不直接支持通配符删除，我们需要手动处理
            $this->clearCacheByPattern($pattern);
        }
    }

    /**
     * 根据单个模式清理缓存
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // 移除通配符
        $prefix = str_replace('*', '', $pattern);
        
        // 获取所有可能的缓存键（这里我们使用预定义的键列表）
        $possibleKeys = $this->generatePossibleCacheKeys($prefix);
        
        foreach ($possibleKeys as $key) {
            if (Cache::has($key)) {
                Cache::forget($key);
                Log::debug("Cleared cache key: {$key}");
            }
        }
    }

    /**
     * 生成可能的缓存键
     */
    private function generatePossibleCacheKeys(string $prefix): array
    {
        $keys = [];
        
        // 根据前缀生成可能的键
        switch ($prefix) {
            case 'telegram_menu_':
                // 菜单相关的键
                $menuIds = TelegramMenuItem::pluck('id')->toArray();
                foreach ($menuIds as $id) {
                    $keys[] = "telegram_menu_{$id}";
                    $keys[] = "telegram_menu_tree_{$id}";
                    $keys[] = "telegram_menu_children_{$id}";
                }
                $keys[] = 'telegram_menu_all';
                $keys[] = 'telegram_menu_active';
                $keys[] = 'telegram_menu_structure';
                break;
                
            case 'telegram_image_':
                // 图片相关的键
                $imageIds = TelegramMenuImage::pluck('id')->toArray();
                foreach ($imageIds as $id) {
                    $keys[] = "telegram_image_{$id}";
                    $keys[] = "telegram_image_thumbnails_{$id}";
                }
                $keys[] = 'telegram_images_all';
                break;
                
            case 'telegram_language_':
                // 语言相关的键
                $languageCodes = TelegramLanguage::pluck('code')->toArray();
                foreach ($languageCodes as $code) {
                    $keys[] = "telegram_language_{$code}";
                    $keys[] = "telegram_translations_{$code}";
                }
                $keys[] = 'telegram_languages_all';
                $keys[] = 'telegram_languages_active';
                break;
                
            case 'telegram_menu_stats_':
                // 统计相关的键
                $keys[] = 'telegram_menu_stats_overview';
                $keys[] = 'telegram_menu_stats_popular_items';
                $keys[] = 'telegram_menu_stats_daily';
                $keys[] = 'telegram_menu_stats_weekly';
                $keys[] = 'telegram_menu_stats_monthly';
                break;
        }
        
        return $keys;
    }

    /**
     * 重新构建菜单缓存
     */
    private function rebuildMenuCache(): void
    {
        // 预热常用的菜单缓存
        $activeMenus = TelegramMenuItem::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        foreach ($activeMenus as $menu) {
            Cache::put("telegram_menu_{$menu->id}", $menu, 3600);
        }
        
        // 缓存菜单结构
        $menuStructure = TelegramMenuItem::with(['children', 'translations', 'images'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        Cache::put('telegram_menu_structure', $menuStructure, 3600);
        
        Log::info('Menu cache rebuilt successfully');
    }

    /**
     * 重新构建语言缓存
     */
    private function rebuildLanguageCache(): void
    {
        $activeLanguages = TelegramLanguage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        Cache::put('telegram_languages_active', $activeLanguages, 3600);
        
        foreach ($activeLanguages as $language) {
            Cache::put("telegram_language_{$language->code}", $language, 3600);
        }
        
        Log::info('Language cache rebuilt successfully');
    }

    /**
     * 清理孤立的数据
     */
    private function cleanupOrphanedData(): void
    {
        // 清理孤立的图片文件
        $this->cleanupOrphanedImageFiles();
        
        // 清理孤立的翻译记录
        $this->cleanupOrphanedTranslations();
        
        // 清理过期的统计数据
        $this->cleanupExpiredStats();
    }

    /**
     * 清理孤立的图片文件
     */
    private function cleanupOrphanedImageFiles(): void
    {
        $disk = Storage::disk('public');
        $imageDirectory = 'telegram/images';
        
        if (!$disk->exists($imageDirectory)) {
            return;
        }
        
        $allFiles = $disk->allFiles($imageDirectory);
        $dbImages = TelegramMenuImage::pluck('path')->toArray();
        
        $orphanedFiles = array_diff($allFiles, $dbImages);
        
        foreach ($orphanedFiles as $file) {
            $disk->delete($file);
            Log::info("Deleted orphaned image file: {$file}");
        }
        
        Log::info("Cleaned up " . count($orphanedFiles) . " orphaned image files");
    }

    /**
     * 清理孤立的翻译记录
     */
    private function cleanupOrphanedTranslations(): void
    {
        // 这里可以添加清理孤立翻译记录的逻辑
        // 例如：删除没有对应菜单项的翻译记录
    }

    /**
     * 清理过期的统计数据
     */
    private function cleanupExpiredStats(): void
    {
        // 删除超过指定天数的统计数据
        $retentionDays = $this->options['stats_retention_days'] ?? 90;
        $cutoffDate = now()->subDays($retentionDays);
        
        $deletedCount = \App\Models\TelegramMenuStat::where('created_at', '<', $cutoffDate)
            ->delete();
            
        Log::info("Cleaned up {$deletedCount} expired stats records older than {$retentionDays} days");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ClearTelegramCacheJob failed ({$this->cacheType}): " . $exception->getMessage());
    }
}