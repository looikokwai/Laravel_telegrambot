<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramMenuItem;
use App\Models\TelegramMenuTranslation;
use App\Models\TelegramLanguage;

class TelegramMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 获取中文语言ID
        $chineseLanguage = TelegramLanguage::where('code', 'zh')->first();
        $englishLanguage = TelegramLanguage::where('code', 'en')->first();
        $malayLanguage = TelegramLanguage::where('code', 'ms')->first();

        if (!$chineseLanguage || !$englishLanguage || !$malayLanguage) {
            $this->command->error('请先运行 TelegramLanguageSeeder（需包含 zh/en/ms）');
            return;
        }

        // 创建根菜单项
        $rootMenus = [
            [
                'key' => 'welcome_message',
                'type' => 'submenu',
                'callback_data' => 'welcome_message',
                'sort_order' => 0,
                'translations' => [
                    'zh' => ['title' => '欢迎菜单', 'description' => '欢迎消息菜单'],
                    'en' => ['title' => 'Welcome Menu', 'description' => 'Welcome message menu'],
                    'ms' => ['title' => 'Menu Selamat Datang', 'description' => 'Menu mesej alu-aluan'],
                ]
            ],
            [
                'key' => 'language',
                'type' => 'callback',
                'callback_data' => 'language',
                'sort_order' => 1,
                'translations' => [
                    'zh' => ['title' => '更换语言', 'description' => '点击更换语言'],
                    'en' => ['title' => 'Language', 'description' => 'Click to change language'],
                    'ms' => ['title' => 'Bahasa', 'description' => 'Klik untuk mengubah bahasa'],
                ]
            ],
            [
                'key' => 'language_changed',
                'type' => 'callback',
                'callback_data' => 'language_changed',
                'sort_order' => 99,
                'translations' => [
                    'zh' => ['title' => '更换成功', 'description' => '语言已更换成功'],
                    'en' => ['title' => 'Language changed', 'description' => 'Language changed successfully'],
                    'ms' => ['title' => 'Bahasa berubah', 'description' => 'Bahasa berubah berjaya'],
                ]
            ],
        ];

        // 创建根菜单项
        $createdRootMenus = [];
        foreach ($rootMenus as $menuData) {
            $menuItem = TelegramMenuItem::updateOrCreate(
                ['key' => $menuData['key']],
                [
                    'parent_id' => null,
                    'type' => $menuData['type'],
                    'callback_data' => $menuData['callback_data'],
                    'url' => null,
                    'is_active' => true,
                    'sort_order' => $menuData['sort_order'],
                    'metadata' => null,
                ]
            );

            // 创建翻译
            foreach ($menuData['translations'] as $langCode => $translation) {
                $language = match ($langCode) {
                    'zh' => $chineseLanguage,
                    'en' => $englishLanguage,
                    'ms' => $malayLanguage,
                    default => $englishLanguage,
                };
                TelegramMenuTranslation::updateOrCreate(
                    [
                        'menu_item_id' => $menuItem->id,
                        'language_id' => $language->id,
                    ],
                    [
                        'title' => $translation['title'],
                        'description' => $translation['description'],
                    ]
                );
            }

            $createdRootMenus[$menuData['key']] = $menuItem;
        }

        // 创建子菜单项
        $subMenus = [
            // welcome_message的子菜单
            [
                'parent_key' => 'welcome_message',
                'items' => [
                    [
                        'key' => 'test',
                        'type' => 'callback',
                        'callback_data' => 'test_action',
                        'sort_order' => 1,
                        'translations' => [
                            'zh' => ['title' => 'test', 'description' => '测试选项'],
                            'en' => ['title' => 'test', 'description' => 'Test option'],
                        ]
                    ],
                    [
                        'key' => 'test2',
                        'type' => 'callback',
                        'callback_data' => 'test2_action',
                        'sort_order' => 2,
                        'translations' => [
                            'zh' => ['title' => 'test2', 'description' => '测试选项2'],
                            'en' => ['title' => 'test2', 'description' => 'Test option 2'],
                        ]
                    ],
                ]
            ],
        ];

        // 创建子菜单项
        foreach ($subMenus as $subMenuGroup) {
            $parentMenu = $createdRootMenus[$subMenuGroup['parent_key']];

            foreach ($subMenuGroup['items'] as $itemData) {
                $menuItem = TelegramMenuItem::updateOrCreate(
                    ['key' => $itemData['key']],
                    [
                        'parent_id' => $parentMenu->id,
                        'type' => $itemData['type'],
                        'callback_data' => $itemData['callback_data'],
                        'url' => null,
                        'is_active' => true,
                        'sort_order' => $itemData['sort_order'],
                        'metadata' => null,
                    ]
                );

                // 创建翻译
                foreach ($itemData['translations'] as $langCode => $translation) {
                    $language = match ($langCode) {
                        'zh' => $chineseLanguage,
                        'en' => $englishLanguage,
                        'ms' => $malayLanguage,
                        default => $englishLanguage,
                    };
                    TelegramMenuTranslation::updateOrCreate(
                        [
                            'menu_item_id' => $menuItem->id,
                            'language_id' => $language->id,
                        ],
                        [
                            'title' => $translation['title'],
                            'description' => $translation['description'],
                        ]
                    );
                }
            }
        }

        $this->command->info('菜单数据已成功创建！');
        $this->command->info('根菜单: welcome_message, language, A, B, C');
        $this->command->info('welcome_message的子菜单: test, test2');
        $this->command->info('A的子菜单: aa, bb, cc');
        $this->command->info('B的子菜单: dd, ee, ff');
        $this->command->info('C的子菜单: gg, hh, ii');
    }
}
