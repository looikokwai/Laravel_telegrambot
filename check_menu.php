<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TelegramMenuItem;

echo "Checking welcome_message menu item...\n";

// 查找welcome_message菜单项
$welcomeMenuItem = TelegramMenuItem::where('key', 'welcome_message')->first();

if ($welcomeMenuItem) {
    echo "Welcome message menu item found:\n";
    echo "ID: {$welcomeMenuItem->id}\n";
    echo "Key: {$welcomeMenuItem->key}\n";
    echo "Type: {$welcomeMenuItem->type}\n";
    echo "Callback Data: {$welcomeMenuItem->callback_data}\n";
    echo "Parent ID: {$welcomeMenuItem->parent_id}\n";
    echo "\n";
    
    // 查找子菜单
    echo "Looking for submenus with parent_id = {$welcomeMenuItem->id}...\n";
    $submenus = TelegramMenuItem::where('parent_id', $welcomeMenuItem->id)->get();
    
    if ($submenus->count() > 0) {
        echo "Found {$submenus->count()} submenus:\n";
        foreach ($submenus as $submenu) {
            echo "- ID: {$submenu->id}, Key: {$submenu->key}, Callback: {$submenu->callback_data}, Type: {$submenu->type}\n";
        }
    } else {
        echo "No submenus found!\n";
    }
} else {
    echo "Welcome message menu item not found!\n";
}

echo "\nDone.\n";