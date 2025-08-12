<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramLanguage;

class TelegramLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'code' => 'zh',
                'name' => '中文',
                'native_name' => '中文',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'ms',
                'name' => 'Bahasa Melayu',
                'native_name' => 'Bahasa Melayu',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($languages as $language) {
            TelegramLanguage::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
