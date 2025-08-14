<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\BroadcastMessage;
use App\Jobs\SendTelegramMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class TelegramBroadcastService
{
    public function __construct(
        private TelegramUserService $userService
    ) {}

    /**
     * 群发消息给指定目标用户
     */
    public function broadcast(
        string $message,
        string $target = 'active',
        array $options = [],
        ?string $imagePath = null,
        ?array $keyboard = null
    ): array {
        // 1. 创建广播记录
        $broadcastMessage = BroadcastMessage::create([
            'message' => $message,
            'target' => $target,
            'image_path' => $imagePath,
            'keyboard' => $keyboard,
            'total_users' => 0, // 稍后更新
            'status' => 'pending'
        ]);

        // 2. 获取目标用户
        $users = $this->getTargetUsers($target);

        // 3. 更新用户总数
        $broadcastMessage->update(['total_users' => $users->count()]);

        // 4. 发送消息到队列
        foreach ($users as $user) {
            try {
                SendTelegramMessage::dispatch(
                    $user->chat_id,
                    $message,
                    $options,
                    $imagePath,
                    $keyboard,
                    $broadcastMessage->id, // 传递广播ID用于跟踪
                    '' // uniqueId
                );
            } catch (\Exception $e) {
                Log::error('Failed to queue broadcast message', [
                    'broadcast_id' => $broadcastMessage->id,
                    'user_id' => $user->id,
                    'chat_id' => $user->chat_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 5. 初始状态设为 pending，等待 Job 完成后更新
        $broadcastMessage->update([
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => 'pending',
            'sent_at' => null
        ]);

        return [
            'broadcast_id' => $broadcastMessage->id,
            'total_users' => $users->count(),
            'sent' => 0, // 初始为0，等待Job完成后更新
            'failed' => 0, // 初始为0，等待Job完成后更新
            'target' => $target
        ];
    }

    /**
     * 获取目标用户
     */
    private function getTargetUsers(string $target): Collection
    {
        return match ($target) {
            'all' => TelegramUser::all(),
            'recent' => $this->userService->getRecentlyActiveUsers(7),
            'recent_30' => $this->userService->getRecentlyActiveUsers(30),
            'inactive' => TelegramUser::where('is_active', false)->get(),
            default => $this->userService->getActiveUsers()
        };
    }


    /**
     * 获取群发统计信息
     */
    public function getBroadcastStats(): array
    {
        $userStats = $this->userService->getUserStats();
        $broadcastStats = $this->getBroadcastMessageStats();

        return [
            'available_targets' => [
                'all' => $userStats['total_users'],
                'active' => $userStats['active_users'],
                'inactive' => $userStats['inactive_users'],
                'recent_7_days' => $this->userService->getRecentlyActiveUsers(7)->count(),
                'recent_30_days' => $this->userService->getRecentlyActiveUsers(30)->count(),
            ],
            'language_distribution' => $userStats['language_distribution'],
            'users_with_language_selected' => $userStats['users_with_language_selected'],
            'broadcast_stats' => $broadcastStats
        ];
    }

    /**
     * 获取广播历史
     */
    public function getBroadcastHistory(int $perPage = 20): LengthAwarePaginator
    {
        return BroadcastMessage::orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 获取广播消息统计
     */
    public function getBroadcastMessageStats(): array
    {
        $totalBroadcasts = BroadcastMessage::count();
        $totalSent = BroadcastMessage::sum('sent_count');
        $totalFailed = BroadcastMessage::sum('failed_count');
        $recentBroadcasts = BroadcastMessage::recent(7)->count();

        return [
            'total_broadcasts' => $totalBroadcasts,
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'success_rate' => $totalSent > 0 ? round(($totalSent - $totalFailed) / $totalSent * 100, 2) : 0,
            'recent_broadcasts' => $recentBroadcasts
        ];
    }
}
