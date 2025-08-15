<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TelegramChannelMessage extends Model
{
    protected $fillable = [
        'channel_id',
        'message_id',
        'message_text',
        'image_path',
        'keyboard',
        'sent_by',
        'status',
        'error_message',
        'sent_at'
    ];

    protected $casts = [
        'keyboard' => 'array',
        'sent_at' => 'datetime'
    ];

    /**
     * 频道关系
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class, 'channel_id');
    }

    /**
     * 成功消息作用域
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * 失败消息作用域
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Bot 发送消息作用域
     */
    public function scopeSentByBot(Builder $query): Builder
    {
        return $query->where('sent_by', 'bot');
    }

    /**
     * 管理员发送消息作用域
     */
    public function scopeSentByAdmin(Builder $query): Builder
    {
        return $query->where('sent_by', 'admin');
    }

    /**
     * 最近消息作用域
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 检查是否包含图片
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }

    /**
     * 检查是否包含键盘
     */
    public function hasKeyboard(): bool
    {
        return !empty($this->keyboard);
    }

    /**
     * 标记为发送成功
     */
    public function markAsSent(string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'message_id' => $messageId,
            'sent_at' => now()
        ]);
    }

    /**
     * 标记为发送失败
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'sent_at' => now()
        ]);
    }
}
