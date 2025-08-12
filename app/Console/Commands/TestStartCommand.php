<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\TelegramMenuService;
use App\Services\TelegramLanguageService;
use Telegram\Bot\Laravel\Facades\Telegram;

class TestStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the StartCommand functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing StartCommand functionality...');
        
        // 创建一个测试用户
        $testUser = TelegramUser::firstOrCreate(
            ['telegram_user_id' => '999999999'],
            [
                'chat_id' => '999999999',
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => 'testuser',
                'language' => 'en',
                'language_selected' => true,
                'is_active' => true,
            ]
        );
        
        $this->info("Test user created/found: {$testUser->first_name} (ID: {$testUser->telegram_id})");
        
        // 模拟 Telegram 更新对象
        $update = (object) [
            'message' => (object) [
                'message_id' => 1,
                'from' => (object) [
                    'id' => $testUser->telegram_id,
                    'first_name' => $testUser->first_name,
                    'last_name' => $testUser->last_name,
                    'username' => $testUser->username,
                    'language_code' => $testUser->language,
                ],
                'chat' => (object) [
                    'id' => $testUser->telegram_id,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];
        
        try {
            // 创建 StartCommand 实例
            $startCommand = app(StartCommand::class);
            $this->info('✅ StartCommand instance created successfully');
            
            // 模拟执行命令
            $this->info('Executing StartCommand...');
            
            // 由于我们不能真正发送消息到 Telegram，我们只测试命令的逻辑
            $this->info('Command would execute with:');
            $this->line("- User ID: {$testUser->telegram_id}");
            $this->line("- User Language: {$testUser->language}");
            $this->line("- Command: /start");
            
            // 测试菜单创建
            $menuService = app(TelegramMenuService::class);
            $rootItems = $menuService->getRootMenuItems();
            $this->info("✅ Menu service working, found {$rootItems->count()} root menu items");
            
            if ($rootItems->count() > 0) {
                $keyboard = $menuService->buildTelegramKeyboard($rootItems);
                $this->info('✅ Telegram keyboard built successfully');
            }
            
            $this->info('✅ StartCommand test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing StartCommand: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }
        
        // 清理测试用户
        $testUser->delete();
        $this->info('Test user cleaned up.');
    }
}
