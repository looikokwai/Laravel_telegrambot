<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMenuTranslation extends Model
{
    protected $table = 'telegram_menu_translations';
    
    protected $fillable = [
        'menu_item_id',
        'language_id',
        'title',
        'description',
    ];
    
    /**
     * 获取关联的菜单项
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(TelegramMenuItem::class, 'menu_item_id');
    }
    
    /**
     * 获取关联的语言
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(TelegramLanguage::class, 'language_id');
    }
    
    /**
     * 作用域：按语言筛选
     */
    public function scopeForLanguage($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
    
    /**
     * 作用域：按菜单项筛选
     */
    public function scopeForMenuItem($query, $menuItemId)
    {
        return $query->where('menu_item_id', $menuItemId);
    }
}
