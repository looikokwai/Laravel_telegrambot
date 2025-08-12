<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TelegramMenuItem;

echo "=== 当前菜单结构 ===\n";

// 获取所有菜单项
$menuItems = TelegramMenuItem::with('translations')->orderBy('parent_id')->orderBy('sort_order')->get();

foreach ($menuItems as $item) {
    $indent = $item->parent_id ? '  ' : '';
    echo $indent . $item->key . ' (type: ' . $item->type . ', parent_id: ' . ($item->parent_id ?: 'null') . ', callback_data: ' . ($item->callback_data ?: 'null') . ')' . "\n";
    
    // 显示翻译
    foreach ($item->translations as $translation) {
        echo $indent . '  - ' . $translation->language_code . ': ' . $translation->title . "\n";
    }
}

echo "\n=== 检查welcome_message菜单项 ===\n";
$welcomeMenuItem = TelegramMenuItem::where('key', 'welcome_message')
    ->orWhere('callback_data', 'welcome_message')
    ->first();

if ($welcomeMenuItem) {
    echo "找到welcome_message菜单项:\n";
    echo "- key: {$welcomeMenuItem->key}\n";
    echo "- type: {$welcomeMenuItem->type}\n";
    echo "- parent_id: " . ($welcomeMenuItem->parent_id ?: 'null') . "\n";
    echo "- callback_data: " . ($welcomeMenuItem->callback_data ?: 'null') . "\n";
    
    // 获取子菜单
    $children = TelegramMenuItem::where('parent_id', $welcomeMenuItem->id)->get();
    if ($children->count() > 0) {
        echo "子菜单项:\n";
        foreach ($children as $child) {
            echo "  - {$child->key} (type: {$child->type})\n";
        }
    } else {
        echo "没有子菜单项\n";
    }
} else {
    echo "未找到welcome_message菜单项\n";
}