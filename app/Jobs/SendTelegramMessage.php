<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Log;

class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $chatId,
        public string $message,
        public array $options = [],
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

        try {
            $params = array_merge([
                'chat_id' => $this->chatId,
                'text' => $this->message,
                'parse_mode' => 'HTML'
            ], $this->options);

            Telegram::sendMessage($params);
            
            // Log when the message is successfully sent.
            Log::info("Telegram message sent to chat: {$this->chatId}", ['unique_id' => $this->uniqueId]);
        } catch (\Exception $e) {
            // Log if an error occurs.
            Log::error("Failed to send Telegram message: " . $e->getMessage(), ['unique_id' => $this->uniqueId]);
            throw $e;
        }
    }
}
