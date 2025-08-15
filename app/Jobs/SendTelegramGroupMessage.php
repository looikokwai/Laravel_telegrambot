<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramGroup;
use App\Models\TelegramGroupMessage;
use App\Models\TelegramGroupBroadcastMessage;

class SendTelegramGroupMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $groupId,
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
        Log::info('Processing Telegram group message job', [
            'group_id' => $this->groupId,
            'broadcast_id' => $this->broadcastId
        ]);

        try {
            // 查找群组
            $group = TelegramGroup::where('group_id', $this->groupId)->first();

            if (!$group) {
                throw new \Exception("Group not found: {$this->groupId}");
            }

            if (!$group->is_active) {
                throw new \Exception("Group is inactive: {$this->groupId}");
            }

            // 创建消息记录
            $messageRecord = TelegramGroupMessage::create([
                'group_id' => $group->id,
                'message_text' => $this->message,
                'image_path' => $this->imagePath,
                'keyboard' => $this->keyboard,
                'sent_by' => 'admin',
                'status' => 'pending'
            ]);

            // 发送消息
            if ($this->imagePath) {
                // 发送图片消息
                $this->sendPhotoMessage($group, $messageRecord);
            } else {
                // 发送文本消息
                $this->sendTextMessage($group, $messageRecord);
            }

            // 更新群组最后活跃时间
            $group->updateLastActivity();

            // 更新广播统计
            if ($this->broadcastId) {
                $this->updateBroadcastStats(true);
            }

            Log::info("Telegram group message sent successfully", [
                'group_id' => $this->groupId,
                'message_record_id' => $messageRecord->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send Telegram group message: " . $e->getMessage(), [
                'group_id' => $this->groupId,
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
    private function sendPhotoMessage(TelegramGroup $group, TelegramGroupMessage $messageRecord): void
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
            'chat_id' => $group->group_id,
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
    private function sendTextMessage(TelegramGroup $group, TelegramGroupMessage $messageRecord): void
    {
        $params = [
            'chat_id' => $group->group_id,
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
            $broadcast = TelegramGroupBroadcastMessage::find($this->broadcastId);

            if ($broadcast) {
                if ($success) {
                    $broadcast->incrementSentCount();
                } else {
                    $broadcast->incrementFailedCount();
                }

                // 检查是否所有消息都已发送
                $totalSent = $broadcast->sent_count + $broadcast->failed_count;
                if ($totalSent >= $broadcast->total_groups) {
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
        Log::error('Telegram group message job finally failed', [
            'group_id' => $this->groupId,
            'broadcast_id' => $this->broadcastId,
            'error' => $exception->getMessage()
        ]);

        // 更新广播统计
        if ($this->broadcastId) {
            $this->updateBroadcastStats(false);
        }
    }
}
