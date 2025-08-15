<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TelegramChannelBroadcastMessage extends Model
{
    protected $fillable = [
        'message',
        'target_channels',
        'target_type',
        'image_path',
        'keyboard',
        'total_channels',
        'sent_count',
        'failed_count',
        'status',
        'sent_at'
    ];

    protected $casts = [
        'target_channels' => 'array',
        'keyboard' => 'array',
        'sent_at' => 'datetime',
        'total_channels' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer'
    ];

    /**
     * 待处理广播作用域
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * 已完成广播作用域
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * 部分成功广播作用域
     */
    public function scopeCompletedWithErrors(Builder $query): Builder
    {
        return $query->where('status', 'completed_with_errors');
    }

    /**
     * 失败广播作用域
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * 按目标类型筛选
     */
    public function scopeByTargetType(Builder $query, string $targetType): Builder
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * 最近广播作用域
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 获取成功率
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_channels === 0) {
            return 0;
        }
        return round(($this->sent_count - $this->failed_count) / $this->total_channels * 100, 2);
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
     * 更新发送状态
     */
    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    /**
     * 增加发送计数
     */
    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
    }

    /**
     * 增加失败计数
     */
    public function incrementFailedCount(): void
    {
        $this->increment('failed_count');
    }
}
