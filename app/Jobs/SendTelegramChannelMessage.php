<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramChannel;
use App\Models\TelegramChannelMessage;
use App\Models\TelegramChannelBroadcastMessage;

class SendTelegramChannelMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $channelId,
        public string $message,
        public array $options = [],
        public ?string $imagePath = null,
        public ?array $keyboard = null,
        public ?int $broadcastId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing Telegram channel message job', [
            'channel_id' => $this->channelId,
            'broadcast_id' => $this->broadcastId
        ]);

        try {
            // 查找频道
            $channel = TelegramChannel::where('channel_id', $this->channelId)->first();

            if (!$channel) {
                throw new \Exception("Channel not found: {$this->channelId}");
            }

            if (!$channel->is_active) {
                throw new \Exception("Channel is inactive: {$this->channelId}");
            }

            // 创建消息记录
            $messageRecord = TelegramChannelMessage::create([
                'channel_id' => $channel->id,
                'message_text' => $this->message,
                'image_path' => $this->imagePath,
                'keyboard' => $this->keyboard,
                'sent_by' => 'admin',
                'status' => 'pending'
            ]);

            // 发送消息
            if ($this->imagePath) {
                // 发送图片消息
                $this->sendPhotoMessage($channel, $messageRecord);
            } else {
                // 发送文本消息
                $this->sendTextMessage($channel, $messageRecord);
            }

            // 更新频道最后活跃时间
            $channel->updateLastActivity();

            // 更新广播统计
            if ($this->broadcastId) {
                $this->updateBroadcastStats(true);
            }

            Log::info("Telegram channel message sent successfully", [
                'channel_id' => $this->channelId,
                'message_record_id' => $messageRecord->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send Telegram channel message: " . $e->getMessage(), [
                'channel_id' => $this->channelId,
                'broadcast_id' => $this->broadcastId,
                'error' => $e
            ]);

            // 更新消息记录为失败
            if (isset($messageRecord)) {
                $messageRecord->markAsFailed($e->getMessage());
            }

            // 更新广播统计
            if ($this->broadcastId) {
                $this->updateBroadcastStats(false);
            }

            throw $e;
        }
    }

    /**
     * 发送图片消息
     */
    private function sendPhotoMessage(TelegramChannel $channel, TelegramChannelMessage $messageRecord): void
    {
        $fullPath = storage_path('app/private/' . $this->imagePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("Image file not found: {$fullPath}");
        }

        $inputFile = \Telegram\Bot\FileUpload\InputFile::create(
            $fullPath,
            basename($this->imagePath)
        );

        $params = [
            'chat_id' => $channel->channel_id,
            'photo' => $inputFile,
            'caption' => $this->message,
            'parse_mode' => 'HTML'
        ];

        if ($this->keyboard) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => $this->keyboard
            ]);
        }

        $response = Telegram::sendPhoto($params);
        $messageRecord->markAsSent($response['message_id']);
    }

    /**
     * 发送文本消息
     */
    private function sendTextMessage(TelegramChannel $channel, TelegramChannelMessage $messageRecord): void
    {
        $params = [
            'chat_id' => $channel->channel_id,
            'text' => $this->message,
            'parse_mode' => 'HTML'
        ];

        if ($this->keyboard) {
            $params['reply_markup'] = json_encode([
                'inline_keyboard' => $this->keyboard
            ]);
        }

        $response = Telegram::sendMessage($params);
        $messageRecord->markAsSent($response['message_id']);
    }

    /**
     * 更新广播统计
     */
    private function updateBroadcastStats(bool $success): void
    {
        if (!$this->broadcastId) {
            return;
        }

        try {
            $broadcast = TelegramChannelBroadcastMessage::find($this->broadcastId);

            if ($broadcast) {
                if ($success) {
                    $broadcast->incrementSentCount();
                } else {
                    $broadcast->incrementFailedCount();
                }

                // 检查是否所有消息都已发送
                $totalSent = $broadcast->sent_count + $broadcast->failed_count;
                if ($totalSent >= $broadcast->total_channels) {
                    if ($broadcast->failed_count === 0) {
                        $broadcast->updateStatus('completed');
                    } elseif ($broadcast->sent_count > 0) {
                        $broadcast->updateStatus('completed_with_errors');
                    } else {
                        $broadcast->updateStatus('failed');
                    }
                    $broadcast->update(['sent_at' => now()]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update broadcast stats: ' . $e->getMessage(), [
                'broadcast_id' => $this->broadcastId,
                'error' => $e
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Telegram channel message job finally failed', [
            'channel_id' => $this->channelId,
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage()
        ]);

        // 更新广播统计
        if ($this->broadcastId) {
            $this->updateBroadcastStats(false);
        }
    }
}
