<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramLanguage extends Model
{
    protected $table = 'telegram_languages';

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_emoji',
        'is_rtl',
        'is_active',
        'is_default',
        'sort_order',
        'selection_title',
        'selection_prompt',
        'back_label',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_rtl' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 获取该语言的菜单翻译
     */
    public function menuTranslations(): HasMany
    {
        return $this->hasMany(TelegramMenuTranslation::class, 'language_id');
    }

    /**
     * 获取该语言的菜单项图片
     */
    public function menuItemImages(): HasMany
    {
        return $this->hasMany(TelegramMenuItemImage::class, 'language_id');
    }

    /**
     * 获取该语言关联的图片
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(TelegramMenuImage::class, 'telegram_language_images', 'language_id', 'image_id')
                    ->withPivot(['type', 'sort_order'])
                    ->withTimestamps();
    }

    /**
     * 获取语言图片关联
     */
    public function languageImages(): HasMany
    {
        return $this->hasMany(TelegramLanguageImage::class, 'language_id');
    }

    /**
     * 获取选择提示图片
     */
    public function getSelectionImageAttribute()
    {
        $languageImage = $this->languageImages()->ofType('selection')->first();
        return $languageImage ? $languageImage->image : null;
    }

    /**
     * 作用域：仅获取启用的语言
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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * 作用域：获取默认语言
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
