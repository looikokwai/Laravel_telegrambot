<?php

namespace App\Jobs;

use App\Models\TelegramMenuStat;
use App\Models\TelegramMenuItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateTelegramMenuStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $menuItemId;
    protected $userId;
    protected $action;
    protected $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(int $menuItemId, ?int $userId = null, string $action = 'click', array $metadata = [])
    {
        $this->menuItemId = $menuItemId;
        $this->userId = $userId;
        $this->action = $action;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 验证菜单项是否存在
            $menuItem = TelegramMenuItem::find($this->menuItemId);
            if (!$menuItem) {
                Log::warning("Menu item {$this->menuItemId} not found for stats update");
                return;
            }

            // 记录统计数据
            $this->recordStats();

            // 更新缓存的统计数据
            $this->updateCachedStats();

            // 更新菜单项的点击计数
            $this->updateMenuItemStats();

            Log::debug("Menu stats updated for item {$this->menuItemId}, action: {$this->action}");

        } catch (\Exception $e) {
            Log::error("Failed to update menu stats for item {$this->menuItemId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 记录统计数据
     */
    private function recordStats(): void
    {
        $today = now()->toDateString();
        $hour = now()->hour;

        // 使用 upsert 来避免重复插入
        TelegramMenuStat::updateOrCreate(
            [
                'menu_item_id' => $this->menuItemId,
                'date' => $today,
                'hour' => $hour,
                'action' => $this->action,
            ],
            [
                'count' => DB::raw('count + 1'),
                'unique_users' => DB::raw('unique_users + ' . ($this->userId ? 1 : 0)),
                'metadata' => json_encode(array_merge(
                    json_decode(TelegramMenuStat::where([
                        'menu_item_id' => $this->menuItemId,
                        'date' => $today,
                        'hour' => $hour,
                        'action' => $this->action,
                    ])->value('metadata') ?? '{}', true),
                    $this->metadata
                )),
                'updated_at' => now(),
            ]
        );

        // 记录用户特定的统计（如果有用户ID）
        if ($this->userId) {
            $this->recordUserStats($today);
        }
    }

    /**
     * 记录用户特定的统计
     */
    private function recordUserStats(string $date): void
    {
        // 检查用户今天是否已经点击过这个菜单项
        $existingUserStat = TelegramMenuStat::where([
            'menu_item_id' => $this->menuItemId,
            'date' => $date,
            'action' => $this->action,
        ])->first();

        if ($existingUserStat) {
            $userClicks = json_decode($existingUserStat->metadata ?? '{}', true);
            $userClicks['user_clicks'] = $userClicks['user_clicks'] ?? [];
            
            // 记录用户点击
            if (!in_array($this->userId, $userClicks['user_clicks'])) {
                $userClicks['user_clicks'][] = $this->userId;
                $userClicks['unique_user_count'] = count($userClicks['user_clicks']);
                
                $existingUserStat->update([
                    'metadata' => json_encode($userClicks),
                    'unique_users' => $userClicks['unique_user_count'],
                ]);
            }
        }
    }

    /**
     * 更新缓存的统计数据
     */
    private function updateCachedStats(): void
    {
        $cacheKeys = [
            "telegram_menu_stats_{$this->menuItemId}_today",
            "telegram_menu_stats_{$this->menuItemId}_week",
            "telegram_menu_stats_{$this->menuItemId}_month",
            "telegram_menu_stats_popular_items",
            "telegram_menu_stats_overview",
        ];

        // 清除相关缓存
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // 重新计算并缓存今日统计
        $this->cacheStatsForPeriod('today');
    }

    /**
     * 缓存指定时期的统计数据
     */
    private function cacheStatsForPeriod(string $period): void
    {
        $cacheKey = "telegram_menu_stats_{$this->menuItemId}_{$period}";
        $cacheDuration = match($period) {
            'today' => 300, // 5分钟
            'week' => 1800, // 30分钟
            'month' => 3600, // 1小时
            default => 300,
        };

        $stats = Cache::remember($cacheKey, $cacheDuration, function () use ($period) {
            return $this->calculateStatsForPeriod($period);
        });
    }

    /**
     * 计算指定时期的统计数据
     */
    private function calculateStatsForPeriod(string $period): array
    {
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $stats = TelegramMenuStat::where('menu_item_id', $this->menuItemId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                action,
                SUM(count) as total_count,
                SUM(unique_users) as total_unique_users,
                COUNT(DISTINCT date) as active_days
            ')
            ->groupBy('action')
            ->get()
            ->keyBy('action')
            ->toArray();

        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'stats' => $stats,
            'total_interactions' => array_sum(array_column($stats, 'total_count')),
            'total_unique_users' => array_sum(array_column($stats, 'total_unique_users')),
            'calculated_at' => now()->toISOString(),
        ];
    }

    /**
     * 更新菜单项的统计信息
     */
    private function updateMenuItemStats(): void
    {
        $menuItem = TelegramMenuItem::find($this->menuItemId);
        if (!$menuItem) {
            return;
        }

        // 计算总点击数
        $totalClicks = TelegramMenuStat::where('menu_item_id', $this->menuItemId)
            ->where('action', 'click')
            ->sum('count');

        // 计算今日点击数
        $todayClicks = TelegramMenuStat::where('menu_item_id', $this->menuItemId)
            ->where('action', 'click')
            ->where('date', now()->toDateString())
            ->sum('count');

        // 计算本周点击数
        $weekClicks = TelegramMenuStat::where('menu_item_id', $this->menuItemId)
            ->where('action', 'click')
            ->where('date', '>=', now()->startOfWeek()->toDateString())
            ->sum('count');

        // 更新菜单项统计字段
        $menuItem->update([
            'click_count' => $totalClicks,
            'stats' => json_encode([
                'total_clicks' => $totalClicks,
                'today_clicks' => $todayClicks,
                'week_clicks' => $weekClicks,
                'last_clicked_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateTelegramMenuStatsJob failed for menu item {$this->menuItemId}: " . $exception->getMessage());
    }
}