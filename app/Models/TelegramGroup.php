<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class TelegramGroup extends Model
{
    protected $fillable = [
        'group_id',
        'title',
        'description',
        'type',
        'username',
        'member_count',
        'is_active',
        'permissions',
        'last_activity'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'permissions' => 'array',
        'last_activity' => 'datetime',
        'member_count' => 'integer'
    ];

    /**
     * 群组消息关系
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TelegramGroupMessage::class, 'group_id');
    }

    /**
     * 群组广播消息关系
     */
    public function broadcastMessages(): HasMany
    {
        return $this->hasMany(TelegramGroupBroadcastMessage::class, 'group_id');
    }

    /**
     * 活跃群组作用域
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 超级群组作用域
     */
    public function scopeSupergroup(Builder $query): Builder
    {
        return $query->where('type', 'supergroup');
    }

    /**
     * 普通群组作用域
     */
    public function scopeNormalGroup(Builder $query): Builder
    {
        return $query->where('type', 'group');
    }

    /**
     * 最近活跃作用域
     */
    public function scopeRecentlyActive(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_activity', '>=', now()->subDays($days));
    }

    /**
     * 按成员数量排序
     */
    public function scopeOrderByMembers(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('member_count', $direction);
    }

    /**
     * 检查是否有发送消息权限
     */
    public function canSendMessages(): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return $this->permissions['can_send_messages'] ?? false;
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
