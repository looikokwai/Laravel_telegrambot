<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TelegramMenuImage extends Model
{
    protected $table = 'telegram_menu_images';
    
    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'alt_text',
        'metadata',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'array',
    ];
    
    protected $appends = [
        'url',
        'thumbnail_url',
        'full_url',
    ];
    
    /**
     * 获取关联的菜单项
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(TelegramMenuItem::class, 'telegram_menu_item_images', 'image_id', 'menu_item_id')
                    ->withPivot(['language_id', 'type', 'sort_order'])
                    ->withTimestamps();
    }
    
    /**
     * 获取菜单项图片关联
     */
    public function menuItemImages(): HasMany
    {
        return $this->hasMany(TelegramMenuItemImage::class, 'image_id');
    }
    
    /**
     * 作用域：按MIME类型筛选
     */
    public function scopeOfMimeType($query, $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }
    
    /**
     * 作用域：按文件大小范围筛选
     */
    public function scopeFileSizeBetween($query, $minSize, $maxSize)
    {
        return $query->whereBetween('file_size', [$minSize, $maxSize]);
    }
    
    /**
     * 获取完整的文件URL
     */
    public function getFullUrlAttribute(): string
    {
        return route('telegram.images.serve', ['filename' => $this->filename]);
    }
    
    /**
     * 获取URL属性（向后兼容）
     */
    public function getUrlAttribute(): string
    {
        return $this->full_url;
    }
    
    /**
     * 获取文件大小的可读格式
     */
    public function getReadableFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * 获取图片尺寸信息
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return $this->width . 'x' . $this->height;
        }
        
        return null;
    }
    
    /**
     * 检查是否为图片文件
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    
    /**
     * 删除文件时同时删除存储的文件
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($image) {
            if ($image->path && Storage::exists($image->path)) {
                Storage::delete($image->path);
            }
        });
    }
    
    /**
     * 获取缩略图 URL
     */
    public function getThumbnailUrlAttribute(): string
    {
        if (isset($this->metadata['thumbnail_path'])) {
            $filename = basename($this->metadata['thumbnail_path']);
            return route('telegram.images.serve', ['filename' => $filename]);
        }
        
        return $this->url;
    }
    
    /**
     * 检查文件是否存在
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->path);
    }
}