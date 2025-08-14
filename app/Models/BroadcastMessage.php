<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BroadcastMessage extends Model
{
    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'message',
        'target',
        'image_path',
        'keyboard',
        'total_users',
        'sent_count',
        'failed_count',
        'status',
        'sent_at'
    ];

    protected $casts = [
        'keyboard' => 'array',
        'sent_at' => 'datetime'
    ];

    /**
     * 作用域：待处理的广播
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * 作用域：已完成的广播
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * 作用域：部分成功的广播
     */
    public function scopeCompletedWithErrors(Builder $query): Builder
    {
        return $query->where('status', 'completed_with_errors');
    }

    /**
     * 作用域：失败的广播
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * 作用域：按目标类型筛选
     */
    public function scopeByTarget(Builder $query, string $target): Builder
    {
        return $query->where('target', $target);
    }

    /**
     * 作用域：最近7天的广播
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
        if ($this->total_users === 0) {
            return 0;
        }
        return round(($this->sent_count - $this->failed_count) / $this->total_users * 100, 2);
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
     * 获取状态显示文本
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => '待处理',
            'completed' => '成功',
            'completed_with_errors' => '部分成功',
            'failed' => '失败',
            default => '未知'
        };
    }

    /**
     * 获取目标用户类型显示文本
     */
    public function getTargetTextAttribute(): string
    {
        return match($this->target) {
            'all' => '所有用户',
            'active' => '活跃用户',
            'recent' => '最近7天活跃',
            'recent_30' => '最近30天活跃',
            'inactive' => '非活跃用户',
            default => $this->target
        };
    }
}
