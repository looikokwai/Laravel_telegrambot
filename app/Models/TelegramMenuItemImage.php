<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMenuItemImage extends Model
{
    protected $table = 'telegram_menu_item_images';
    
    protected $fillable = [
        'menu_item_id',
        'image_id',
        'language_id',
        'type',
        'sort_order',
    ];
    
    protected $casts = [
        'sort_order' => 'integer',
    ];
    
    /**
     * 获取关联的菜单项
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(TelegramMenuItem::class, 'menu_item_id');
    }
    
    /**
     * 获取关联的图片
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(TelegramMenuImage::class, 'image_id');
    }
    
    /**
     * 获取关联的语言
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(TelegramLanguage::class, 'language_id');
    }
    
    /**
     * 作用域：按菜单项筛选
     */
    public function scopeForMenuItem($query, $menuItemId)
    {
        return $query->where('menu_item_id', $menuItemId);
    }
    
    /**
     * 作用域：按语言筛选
     */
    public function scopeForLanguage($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
    
    /**
     * 作用域：按类型筛选
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * 作用域：按排序顺序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
