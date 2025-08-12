<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TelegramMenuStat extends Model
{
    protected $table = 'telegram_menu_stats';
    
    protected $fillable = [
        'menu_item_id',
        'user_id',
        'action',
        'session_id',
        'metadata',
        'action_time',
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'action_time' => 'datetime',
    ];
    
    /**
     * 获取关联的菜单项
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(TelegramMenuItem::class, 'menu_item_id');
    }
    
    /**
     * 获取关联的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * 作用域：按菜单项筛选
     */
    public function scopeForMenuItem($query, $menuItemId)
    {
        return $query->where('menu_item_id', $menuItemId);
    }
    
    /**
     * 作用域：按用户筛选
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * 作用域：按动作类型筛选
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }
    
    /**
     * 作用域：按会话筛选
     */
    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
    
    /**
     * 作用域：按时间范围筛选
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('action_time', [$startDate, $endDate]);
    }
    
    /**
     * 作用域：今天的统计
     */
    public function scopeToday($query)
    {
        return $query->whereDate('action_time', Carbon::today());
    }
    
    /**
     * 作用域：本周的统计
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('action_time', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }
    
    /**
     * 作用域：本月的统计
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('action_time', Carbon::now()->month)
                    ->whereYear('action_time', Carbon::now()->year);
    }
    
    /**
     * 作用域：按时间排序
     */
    public function scopeOrderByTime($query, $direction = 'desc')
    {
        return $query->orderBy('action_time', $direction);
    }
}
