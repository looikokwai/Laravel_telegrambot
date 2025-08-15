<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'language',
        'language_selected',
        'is_active',
        'last_interaction',
        'user_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'language_selected' => 'boolean',
        'last_interaction' => 'datetime'
    ];

    /**
     * 关联到系统用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 用户消息关系
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TelegramUserMessage::class, 'user_id');
    }

    /**
     * 获取活跃用户
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 获取最近活跃的用户
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_interaction', '>=', now()->subDays($days));
    }
}
