<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TelegramUserMessage extends Model
{
    protected $fillable = [
        'user_id',
        'message_text',
        'image_path',
        'keyboard',
        'sent_by',
        'status',
        'telegram_message_id',
        'error_message',
        'sent_at'
    ];

    protected $casts = [
        'keyboard' => 'array',
        'sent_at' => 'datetime'
    ];

    /**
     * 用户关系
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'user_id');
    }

    /**
     * 已发送作用域
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * 失败作用域
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * 待发送作用域
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * 最近消息作用域
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 标记为已发送
     */
    public function markAsSent(string $telegramMessageId): void
    {
        $this->update([
            'status' => 'sent',
            'telegram_message_id' => $telegramMessageId,
            'sent_at' => now(),
            'error_message' => null
        ]);
    }

    /**
     * 标记为失败
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'sent_at' => now()
        ]);
    }
}