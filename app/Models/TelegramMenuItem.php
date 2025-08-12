<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramMenuItem extends Model
{
    protected $table = 'telegram_menu_items';
    
    protected $fillable = [
        'key',
        'parent_id',
        'type',
        'callback_data',
        'url',
        'is_active',
        'sort_order',
        'metadata',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * 获取父级菜单项
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TelegramMenuItem::class, 'parent_id');
    }
    
    /**
     * 获取子级菜单项
     */
    public function children(): HasMany
    {
        return $this->hasMany(TelegramMenuItem::class, 'parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }
    
    /**
     * 获取所有子级菜单项（包括非活跃的）
     */
    public function allChildren(): HasMany
    {
        return $this->hasMany(TelegramMenuItem::class, 'parent_id')
                    ->orderBy('sort_order');
    }
    
    /**
     * 获取菜单翻译
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TelegramMenuTranslation::class, 'menu_item_id');
    }
    
    /**
     * 获取菜单图片关联
     */
    public function menuItemImages(): HasMany
    {
        return $this->hasMany(TelegramMenuItemImage::class, 'menu_item_id');
    }
    
    /**
     * 获取菜单图片
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(TelegramMenuImage::class, 'telegram_menu_item_images', 'menu_item_id', 'image_id')
                    ->withPivot(['language_id', 'type', 'sort_order'])
                    ->withTimestamps();
    }
    
    /**
     * 获取菜单统计
     */
    public function stats(): HasMany
    {
        return $this->hasMany(TelegramMenuStat::class, 'menu_item_id');
    }
    
    /**
     * 作用域：仅获取启用的菜单项
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * 作用域：按排序顺序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
    
    /**
     * 作用域：根级菜单项
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
    
    /**
     * 作用域：按类型筛选
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * 获取指定语言的翻译
     */
    public function getTranslation($languageId = null)
    {
        if ($languageId) {
            return $this->translations()->where('language_id', $languageId)->first();
        }
        
        // 如果没有指定语言ID，返回第一个可用的翻译
        return $this->translations()->first();
    }
    
    /**
     * 获取指定语言和类型的图片
     */
    public function getImage($languageId = null, $type = 'icon')
    {
        $query = $this->menuItemImages()->where('type', $type);
        
        if ($languageId) {
            $query->where('language_id', $languageId);
        } else {
            $query->whereNull('language_id');
        }
        
        return $query->with('image')->first();
    }
    
    /**
     * 检查是否有子菜单
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
