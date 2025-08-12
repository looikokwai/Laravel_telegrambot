<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;
use App\Services\Telegram\Commands\TelegramCommandFactory;
use App\Services\TelegramMenuService;
use App\Services\TelegramLanguageService;
use App\Models\TelegramMenuItem;
use App\Models\TelegramLanguage;

class TestTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Telegram Bot functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Telegram Bot functionality...');
        
        // 1. 检查菜单项
        $menuCount = TelegramMenuItem::count();
        $this->info("Menu items count: {$menuCount}");
        
        if ($menuCount > 0) {
            $menuItems = TelegramMenuItem::with('translations')->get();
            foreach ($menuItems as $item) {
                $this->line("- ID: {$item->id}, Key: {$item->key}, Type: {$item->type}, Active: " . ($item->is_active ? 'Yes' : 'No'));
            }
        }
        
        // 2. 检查语言
        $languageCount = TelegramLanguage::count();
        $this->info("\nLanguages count: {$languageCount}");
        
        // 3. 测试命令工厂
        $this->info('\nTesting command factory...');
        $factory = new TelegramCommandFactory();
        $startCommand = $factory->getCommandHandler('/start');
        
        if ($startCommand) {
            $this->info('✅ StartCommand created successfully');
            $this->info('Command name: ' . $startCommand->getCommandName());
            $this->info('Command description: ' . $startCommand->getDescription());
        } else {
            $this->error('❌ Failed to create StartCommand');
        }
        
        // 4. 测试菜单服务
        $this->info('\nTesting menu service...');
        try {
            $menuService = app(TelegramMenuService::class);
            $rootItems = $menuService->getRootMenuItems();
            $this->info('✅ Menu service working, root items count: ' . $rootItems->count());
        } catch (\Exception $e) {
            $this->error('❌ Menu service error: ' . $e->getMessage());
        }
        
        // 5. 测试语言服务
        $this->info('\nTesting language service...');
        try {
            $languageService = app(TelegramLanguageService::class);
            $this->info('✅ Language service created successfully');
        } catch (\Exception $e) {
            $this->error('❌ Language service error: ' . $e->getMessage());
        }
        
        $this->info('\nTest completed!');
    }
}
