<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class TelegramChannel extends Model
{
    protected $fillable = [
        'channel_id',
        'title',
        'description',
        'type',
        'username',
        'subscriber_count',
        'is_public',
        'is_active',
        'permissions',
        'last_activity'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'last_activity' => 'datetime',
        'subscriber_count' => 'integer'
    ];

    /**
     * 频道消息关系
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TelegramChannelMessage::class, 'channel_id');
    }

    /**
     * 频道广播消息关系
     */
    public function broadcastMessages(): HasMany
    {
        return $this->hasMany(TelegramChannelBroadcastMessage::class, 'channel_id');
    }

    /**
     * 活跃频道作用域
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 公开频道作用域
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * 私有频道作用域
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_public', false);
    }

    /**
     * 最近活跃作用域
     */
    public function scopeRecentlyActive(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_activity', '>=', now()->subDays($days));
    }

    /**
     * 按订阅者数量排序
     */
    public function scopeOrderBySubscribers(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('subscriber_count', $direction);
    }

    /**
     * 检查是否有发布消息权限
     */
    public function canPostMessages(): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return $this->permissions['can_post_messages'] ?? false;
    }

    /**
     * 检查是否是管理员
     */
    public function isAdmin(): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return ($this->permissions['status'] ?? '') === 'administrator';
    }

    /**
     * 获取权限状态
     */
    public function getPermissionStatus(): string
    {
        if (!$this->permissions) {
            return 'unknown';
        }

        return $this->permissions['status'] ?? 'unknown';
    }

    /**
     * 更新最后活跃时间
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }
}
