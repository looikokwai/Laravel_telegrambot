<?php

namespace App\Services;

use App\Models\TelegramChannel;
use App\Models\TelegramChannelBroadcastMessage;
use App\Jobs\SendTelegramChannelMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class TelegramChannelBroadcastService
{
    public function __construct(
        private TelegramChannelService $channelService
    ) {}

    /**
     * 频道广播消息
     */
    public function broadcast(
        string $message,
        array $targetChannels = [],
        string $targetType = 'selected',
        array $options = [],
        ?string $imagePath = null,
        ?array $keyboard = null
    ): array {
        // 1. 创建频道广播记录
        $broadcastMessage = TelegramChannelBroadcastMessage::create([
            'message' => $message,
            'target_channels' => $targetChannels,
            'target_type' => $targetType,
            'image_path' => $imagePath,
            'keyboard' => $keyboard,
            'total_channels' => 0,
            'status' => 'pending'
        ]);

        // 2. 获取目标频道
        $channels = $this->getTargetChannels($targetChannels, $targetType);

        // 3. 更新频道总数
        $broadcastMessage->update(['total_channels' => $channels->count()]);

        // 4. 发送消息到队列
        foreach ($channels as $channel) {
            try {
                SendTelegramChannelMessage::dispatch(
                    $channel->channel_id,
                    $message,
                    $options,
                    $imagePath,
                    $keyboard,
                    $broadcastMessage->id
                );
            } catch (\Exception $e) {
                Log::error('Failed to queue channel broadcast message', [
                    'broadcast_id' => $broadcastMessage->id,
                    'channel_id' => $channel->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'broadcast_id' => $broadcastMessage->id,
            'total_channels' => $channels->count(),
            'target_type' => $targetType
        ];
    }

    /**
     * 获取目标频道
     */
    private function getTargetChannels(array $targetChannels, string $targetType): Collection
    {
        return match ($targetType) {
            'all' => TelegramChannel::where('is_active', true)->get(),
            'selected' => TelegramChannel::whereIn('id', $targetChannels)->where('is_active', true)->get(),
            'active' => TelegramChannel::where('is_active', true)->get(),
            default => collect()
        };
    }

    /**
     * 获取频道广播统计信息
     */
    public function getBroadcastStats(): array
    {
        return [
            'total_broadcasts' => TelegramChannelBroadcastMessage::count(),
            'pending_broadcasts' => TelegramChannelBroadcastMessage::where('status', 'pending')->count(),
            'completed_broadcasts' => TelegramChannelBroadcastMessage::where('status', 'completed')->count(),
            'completed_with_errors_broadcasts' => TelegramChannelBroadcastMessage::where('status', 'completed_with_errors')->count(),
            'failed_broadcasts' => TelegramChannelBroadcastMessage::where('status', 'failed')->count(),
        ];
    }

    /**
     * 获取频道广播历史
     */
    public function getBroadcastHistory(int $perPage = 15): LengthAwarePaginator
    {
        return TelegramChannelBroadcastMessage::orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 获取特定广播详情
     */
    public function getBroadcastDetails(int $broadcastId): ?TelegramChannelBroadcastMessage
    {
        return TelegramChannelBroadcastMessage::find($broadcastId);
    }

    /**
     * 取消广播
     */
    public function cancelBroadcast(int $broadcastId): bool
    {
        try {
            $broadcast = TelegramChannelBroadcastMessage::findOrFail($broadcastId);

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
            $broadcast = TelegramChannelBroadcastMessage::findOrFail($broadcastId);

            if ($broadcast->status !== 'failed') {
                return [
                    'success' => false,
                    'error' => '只能重试失败的广播'
                ];
            }

            // 创建新的广播记录
            $newBroadcast = TelegramChannelBroadcastMessage::create([
                'message' => $broadcast->message,
                'target_channels' => $broadcast->target_channels,
                'target_type' => $broadcast->target_type,
                'image_path' => $broadcast->image_path,
                'keyboard' => $broadcast->keyboard,
                'total_channels' => $broadcast->total_channels,
                'status' => 'pending'
            ]);

            // 重新发送消息
            $channels = $this->getTargetChannels($broadcast->target_channels, $broadcast->target_type);

            foreach ($channels as $channel) {
                try {
                    SendTelegramChannelMessage::dispatch(
                        $channel->channel_id,
                        $broadcast->message,
                        [],
                        $broadcast->image_path,
                        $broadcast->keyboard,
                        $newBroadcast->id
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to queue retry channel broadcast message', [
                        'broadcast_id' => $newBroadcast->id,
                        'channel_id' => $channel->id,
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
