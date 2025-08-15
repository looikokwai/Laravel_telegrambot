<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\TelegramUserMessage;

class SendTelegramUserMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $messageId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $userMessage = TelegramUserMessage::with('user')->find($this->messageId);
            
            if (!$userMessage) {
                Log::error('User message not found', ['message_id' => $this->messageId]);
                return;
            }

            if (!$userMessage->user) {
                Log::error('User not found for message', ['message_id' => $this->messageId]);
                $userMessage->markAsFailed('User not found');
                return;
            }

            $chatId = $userMessage->user->chat_id;
            $message = $userMessage->message_text;

            Log::info('Processing user message', [
                'message_id' => $this->messageId,
                'chat_id' => $chatId,
                'has_image' => !empty($userMessage->image_path),
                'has_keyboard' => !empty($userMessage->keyboard)
            ]);

            // 格式化键盘
            $inlineKeyboard = null;
            if (!empty($userMessage->keyboard)) {
                $inlineKeyboard = $this->formatInlineKeyboard($userMessage->keyboard);
            }

            if (!empty($userMessage->image_path)) {
                // 发送带图片的消息
                $imagePath = Storage::path($userMessage->image_path);
                
                if (!file_exists($imagePath)) {
                    throw new \Exception("Image file not found: {$imagePath}");
                }

                $params = [
                    'chat_id' => $chatId,
                    'photo' => InputFile::create($imagePath),
                    'caption' => $message,
                    'parse_mode' => 'HTML'
                ];

                if ($inlineKeyboard) {
                    $params['reply_markup'] = json_encode([
                        'inline_keyboard' => $inlineKeyboard
                    ]);
                }

                $response = Telegram::sendPhoto($params);
            } else {
                // 发送纯文本消息
                $params = [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];

                if ($inlineKeyboard) {
                    $params['reply_markup'] = json_encode([
                        'inline_keyboard' => $inlineKeyboard
                    ]);
                }

                $response = Telegram::sendMessage($params);
            }

            // 标记为已发送
            $userMessage->markAsSent($response['message_id']);

            Log::info('User message sent successfully', [
                'message_id' => $this->messageId,
                'chat_id' => $chatId,
                'telegram_message_id' => $response['message_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send user message: ' . $e->getMessage(), [
                'message_id' => $this->messageId,
                'attempts' => $this->attempts()
            ]);

            // 如果是最后一次尝试，标记为失败
            if ($this->attempts() >= $this->tries) {
                if (isset($userMessage)) {
                    $userMessage->markAsFailed($e->getMessage());
                }
            }

            throw $e;
        }
    }

    /**
     * 格式化内联键盘为 Telegram 格式
     */
    private function formatInlineKeyboard(array $keyboard): array
    {
        if (empty($keyboard)) {
            return [];
        }

        $inlineKeyboard = [];
        
        foreach ($keyboard as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $keyboardRow = [];
            foreach ($row as $button) {
                if (!is_array($button) || empty($button['text'])) {
                    continue;
                }
                
                $inlineButton = ['text' => $button['text']];
                
                if (!empty($button['url'])) {
                    $inlineButton['url'] = $button['url'];
                } elseif (!empty($button['callback_data'])) {
                    $inlineButton['callback_data'] = $button['callback_data'];
                }
                
                $keyboardRow[] = $inlineButton;
            }
            
            if (!empty($keyboardRow)) {
                $inlineKeyboard[] = $keyboardRow;
            }
        }
        
        return $inlineKeyboard;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('User message job finally failed', [
            'message_id' => $this->messageId,
            'exception' => $exception->getMessage()
        ]);

        try {
            $userMessage = TelegramUserMessage::find($this->messageId);
            if ($userMessage) {
                $userMessage->markAsFailed($exception->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Failed to mark user message as failed: ' . $e->getMessage());
        }
    }
}