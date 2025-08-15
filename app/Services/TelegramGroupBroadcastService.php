<?php

namespace App\Services;

use App\Models\TelegramGroup;
use App\Models\TelegramGroupBroadcastMessage;
use App\Jobs\SendTelegramGroupMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class TelegramGroupBroadcastService
{
    public function __construct(
        private TelegramGroupService $groupService
    ) {}

    /**
     * 群组广播消息
     */
    public function broadcast(
        string $message,
        array $targetGroups = [],
        string $targetType = 'selected',
        array $options = [],
        ?string $imagePath = null,
        ?array $keyboard = null
    ): array {
        // 1. 创建群组广播记录
        $broadcastMessage = TelegramGroupBroadcastMessage::create([
            'message' => $message,
            'target_groups' => $targetGroups,
            'target_type' => $targetType,
            'image_path' => $imagePath,
            'keyboard' => $keyboard,
            'total_groups' => 0,
            'status' => 'pending'
        ]);

        // 2. 获取目标群组
        $groups = $this->getTargetGroups($targetGroups, $targetType);

        // 3. 更新群组总数
        $broadcastMessage->update(['total_groups' => $groups->count()]);

        // 4. 发送消息到队列
        foreach ($groups as $group) {
            try {
                SendTelegramGroupMessage::dispatch(
                    $group->group_id,
                    $message,
                    $options,
                    $imagePath,
                    $keyboard,
                    $broadcastMessage->id
                );
            } catch (\Exception $e) {
                Log::error('Failed to queue group broadcast message', [
                    'broadcast_id' => $broadcastMessage->id,
                    'group_id' => $group->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'broadcast_id' => $broadcastMessage->id,
            'total_groups' => $groups->count(),
            'target_type' => $targetType
        ];
    }

    /**
     * 获取目标群组
     */
    private function getTargetGroups(array $targetGroups, string $targetType): Collection
    {
        return match ($targetType) {
            'all' => TelegramGroup::where('is_active', true)->get(),
            'selected' => TelegramGroup::whereIn('id', $targetGroups)->where('is_active', true)->get(),
            'active' => TelegramGroup::where('is_active', true)->get(),
            default => collect()
        };
    }

    /**
     * 获取群组广播统计信息
     */
    public function getBroadcastStats(): array
    {
        return [
            'total_broadcasts' => TelegramGroupBroadcastMessage::count(),
            'pending_broadcasts' => TelegramGroupBroadcastMessage::where('status', 'pending')->count(),
            'completed_broadcasts' => TelegramGroupBroadcastMessage::where('status', 'completed')->count(),
            'completed_with_errors_broadcasts' => TelegramGroupBroadcastMessage::where('status', 'completed_with_errors')->count(),
            'failed_broadcasts' => TelegramGroupBroadcastMessage::where('status', 'failed')->count(),
        ];
    }

    /**
     * 获取群组广播历史
     */
    public function getBroadcastHistory(int $perPage = 15): LengthAwarePaginator
    {
        return TelegramGroupBroadcastMessage::orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 获取特定广播详情
     */
    public function getBroadcastDetails(int $broadcastId): ?TelegramGroupBroadcastMessage
    {
        return TelegramGroupBroadcastMessage::find($broadcastId);
    }

    /**
     * 取消广播
     */
    public function cancelBroadcast(int $broadcastId): bool
    {
        try {
            $broadcast = TelegramGroupBroadcastMessage::findOrFail($broadcastId);

            if ($broadcast->status === 'pending') {
                $broadcast->update(['status' => 'cancelled']);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to cancel broadcast: ' . $e->getMessage(), [
                'broadcast_id' => $broadcastId,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * 重试失败的广播
     */
    public function retryFailedBroadcast(int $broadcastId): array
    {
        try {
            $broadcast = TelegramGroupBroadcastMessage::findOrFail($broadcastId);

            if ($broadcast->status !== 'failed') {
                return [
                    'success' => false,
                    'error' => '只能重试失败的广播'
                ];
            }

            // 创建新的广播记录
            $newBroadcast = TelegramGroupBroadcastMessage::create([
                'message' => $broadcast->message,
                'target_groups' => $broadcast->target_groups,
                'target_type' => $broadcast->target_type,
                'image_path' => $broadcast->image_path,
                'keyboard' => $broadcast->keyboard,
                'total_groups' => $broadcast->total_groups,
                'status' => 'pending'
            ]);

            // 重新发送消息
            $groups = $this->getTargetGroups($broadcast->target_groups, $broadcast->target_type);

            foreach ($groups as $group) {
                try {
                    SendTelegramGroupMessage::dispatch(
                        $group->group_id,
                        $broadcast->message,
                        [],
                        $broadcast->image_path,
                        $broadcast->keyboard,
                        $newBroadcast->id
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to queue retry group broadcast message', [
                        'broadcast_id' => $newBroadcast->id,
                        'group_id' => $group->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'success' => true,
                'new_broadcast_id' => $newBroadcast->id,
                'message' => '广播重试已加入队列'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to retry broadcast: ' . $e->getMessage(), [
                'broadcast_id' => $broadcastId,
                'error' => $e
            ]);

            return [
                'success' => false,
                'error' => '重试广播失败: ' . $e->getMessage()
            ];
        }
    }
}
