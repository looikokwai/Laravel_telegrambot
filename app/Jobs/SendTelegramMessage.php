<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;
use App\Models\BroadcastMessage;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $backoff = [5, 15, 30]; // 缩短重试间隔
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $chatId,
        public string $message,
        public array $options = [],
        public ?string $imagePath = null,
        public ?array $keyboard = null,
        public ?int $broadcastId = null,
        public string $uniqueId = ''
    ) {
        // If no unique ID is provided, generate one.
        $this->uniqueId = $uniqueId ?: uniqid('msg_');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Log when the job starts processing.
        Log::info('Processing Telegram message job', ['unique_id' => $this->uniqueId, 'chat_id' => $this->chatId]);

        // 添加详细的参数调试信息
        Log::info('Job parameters', [
            'chat_id' => $this->chatId,
            'message' => $this->message,
            'image_path' => $this->imagePath,
            'keyboard' => $this->keyboard,
            'unique_id' => $this->uniqueId
        ]);

        try {
                        // 根据参数自动判断发送类型
            Log::info('Checking message type', [
                'has_image' => !empty($this->imagePath),
                'has_keyboard' => !empty($this->keyboard),
                'image_path' => $this->imagePath
            ]);

            if ($this->imagePath) {
                // 有图片：发送 sendPhoto
                Log::info('Starting photo message processing');
                try {
                    $fullPath = storage_path('app/private/' . $this->imagePath);




                    // 检查文件是否存在
                    if (!file_exists($fullPath)) {
                        throw new \Exception("Image file not found: {$fullPath}");
                    }

                    Log::info('Creating InputFile', [
                        'full_path' => $fullPath,
                        'filename' => basename($this->imagePath),
                        'file_size' => filesize($fullPath),
                        'is_readable' => is_readable($fullPath)
                    ]);

                    // 创建 InputFile 对象 - 使用完整的命名空间路径
                    $inputFile = \Telegram\Bot\FileUpload\InputFile::create(
                        $fullPath,
                        basename($this->imagePath)
                    );

                    Log::info('InputFile created successfully', [
                        'input_file_class' => get_class($inputFile),
                        'input_file_exists' => $inputFile !== null
                    ]);

                    $params = array_merge([
                        'chat_id' => $this->chatId,
                        'photo' => $inputFile,
                        'caption' => $this->message,
                        'parse_mode' => 'HTML'
                    ], $this->options);

                    if ($this->keyboard) {
                        // 有键盘：添加键盘
                        $replyMarkup = json_encode([
                            'inline_keyboard' => $this->keyboard
                        ]);
                        $params['reply_markup'] = $replyMarkup;

                        Log::info('Keyboard markup created', [
                            'keyboard_data' => $this->keyboard,
                            'reply_markup' => $replyMarkup
                        ]);
                    }

                    Log::info('Sending photo message', [
                        'chat_id' => $params['chat_id'],
                        'caption' => $params['caption'],
                        'photo_type' => gettype($params['photo']),
                        'photo_class' => is_object($params['photo']) ? get_class($params['photo']) : 'not_object',
                        'has_keyboard' => isset($params['reply_markup'])
                    ]);

                    Telegram::sendPhoto($params);
                } catch (\Exception $e) {
                    Log::error('Failed to send photo message: ' . $e->getMessage(), [
                        'chat_id' => $this->chatId,
                        'image_path' => $this->imagePath,
                        'unique_id' => $this->uniqueId
                    ]);
                    throw $e;
                }
            } else {
                // 无图片：发送 sendMessage
                Log::info('Starting text message processing');
                $params = array_merge([
                    'chat_id' => $this->chatId,
                    'text' => $this->message,
                    'parse_mode' => 'HTML'
                ], $this->options);

                if ($this->keyboard) {
                    // 有键盘：添加键盘
                    $replyMarkup = json_encode([
                        'inline_keyboard' => $this->keyboard
                    ]);
                    $params['reply_markup'] = $replyMarkup;

                    Log::info('Keyboard markup created for text message', [
                        'keyboard_data' => $this->keyboard,
                        'reply_markup' => $replyMarkup
                    ]);
                }

                Telegram::sendMessage($params);
            }

            // Log when the message is successfully sent.
            Log::info("Telegram message sent to chat: {$this->chatId}", [
                'unique_id' => $this->uniqueId,
                'broadcast_id' => $this->broadcastId,
                'attempts' => $this->attempts()
            ]);

            // 更新广播统计 - 发送成功
            $this->updateBroadcastStats(true);
        } catch (\Exception $e) {
            // Log if an error occurs.
            Log::error("Failed to send Telegram message: " . $e->getMessage(), ['unique_id' => $this->uniqueId]);
            throw $e;
        }
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
            $broadcast = BroadcastMessage::find($this->broadcastId);
            if (!$broadcast) {
                Log::warning("Broadcast message not found: {$this->broadcastId}");
                return;
            }

            // 使用数据库事务确保统计准确性
            \DB::transaction(function () use ($broadcast, $success) {
                $broadcast->refresh(); // 重新加载最新数据

                // 检查是否已经处理过这个用户
                $cacheKey = "broadcast_{$this->broadcastId}_user_{$this->chatId}";
                if (\Cache::has($cacheKey)) {
                    Log::info("User already processed, skipping", [
                        'broadcast_id' => $this->broadcastId,
                        'chat_id' => $this->chatId,
                        'cache_key' => $cacheKey
                    ]);
                    return;
                }

                if ($success) {
                    $broadcast->increment('sent_count');
                    \Cache::put($cacheKey, 'sent', 3600); // 缓存1小时
                    Log::info("Broadcast sent count incremented", [
                        'broadcast_id' => $this->broadcastId,
                        'chat_id' => $this->chatId,
                        'attempts' => $this->attempts()
                    ]);
                } else {
                    // 只有在最终失败时才增加失败计数
                    if ($this->attempts() >= $this->tries) {
                        $broadcast->increment('failed_count');
                        \Cache::put($cacheKey, 'failed', 3600); // 缓存1小时
                        Log::info("Broadcast failed count incremented (final attempt)", [
                            'broadcast_id' => $this->broadcastId,
                            'chat_id' => $this->chatId,
                            'attempts' => $this->attempts()
                        ]);
                    } else {
                        Log::info("Broadcast attempt failed but will retry", [
                            'broadcast_id' => $this->broadcastId,
                            'chat_id' => $this->chatId,
                            'attempts' => $this->attempts(),
                            'max_attempts' => $this->tries
                        ]);
                    }
                }

                // 检查是否所有消息都已处理完成
                $totalProcessed = $broadcast->sent_count + $broadcast->failed_count;

                // 验证统计准确性
                if ($totalProcessed > $broadcast->total_users) {
                    Log::warning("Statistics mismatch detected", [
                        'broadcast_id' => $this->broadcastId,
                        'sent_count' => $broadcast->sent_count,
                        'failed_count' => $broadcast->failed_count,
                        'total_processed' => $totalProcessed,
                        'total_users' => $broadcast->total_users,
                        'chat_id' => $this->chatId
                    ]);
                }

                if ($totalProcessed >= $broadcast->total_users) {
                    // 所有消息都已处理，更新状态
                    $status = $broadcast->failed_count === 0 ? 'completed' : 'completed_with_errors';
                    $broadcast->update([
                        'status' => $status,
                        'sent_at' => now()
                    ]);

                    Log::info("Broadcast completed", [
                        'broadcast_id' => $this->broadcastId,
                        'status' => $status,
                        'sent_count' => $broadcast->sent_count,
                        'failed_count' => $broadcast->failed_count,
                        'total_users' => $broadcast->total_users,
                        'total_processed' => $totalProcessed
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error("Failed to update broadcast stats: " . $e->getMessage(), [
                'broadcast_id' => $this->broadcastId,
                'success' => $success,
                'attempts' => $this->attempts()
            ]);
        }
    }

    /**
     * 处理Job最终失败
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Telegram message job finally failed", [
            'chat_id' => $this->chatId,
            'unique_id' => $this->uniqueId,
            'attempts' => $this->attempts(),
            'exception' => $exception->getMessage()
        ]);

        // 确保在最终失败时更新统计
        $this->updateBroadcastStats(false);
    }
}
