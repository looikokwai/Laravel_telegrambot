<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;
use App\Services\TelegramMessageService;
use App\Services\TelegramLanguageService;
use App\Services\TelegramMenuService;
use App\Services\Telegram\Commands\StartCommand;
use Illuminate\Support\Facades\Log;

class TestLanguageSelection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-language {chat_id} {language_code=en}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '测试语言选择功能';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $languageCode = $this->argument('language_code');

        $this->info("开始测试语言选择功能...");
        $this->info("Chat ID: {$chatId}");
        $this->info("语言代码: {$languageCode}");

        try {
            // 查找或创建用户
            $user = TelegramUser::where('chat_id', $chatId)->first();
            if (!$user) {
                $this->error("未找到 Chat ID 为 {$chatId} 的用户");
                return 1;
            }

            $this->info("找到用户: ID={$user->id}, Telegram ID={$user->telegram_user_id}");

            // 创建服务实例
            $languageService = new TelegramLanguageService();
            $menuService = new TelegramMenuService();
            $startCommand = new StartCommand($menuService, $languageService);
            $messageService = new TelegramMessageService($languageService, $menuService, $startCommand);

            // 测试语言选择
            $this->info("开始测试语言选择...");

            // 使用反射调用私有方法
            $reflection = new \ReflectionClass($messageService);
            $method = $reflection->getMethod('handleLanguageSelection');
            $method->setAccessible(true);

            $this->info("调用 handleLanguageSelection 方法...");
            $method->invoke($messageService, $user, $languageCode);

            $this->info("语言选择测试完成！");
            $this->info("请检查 Telegram 是否收到消息。");

            // 显示最近的日志
            $this->info("\n最近的日志记录:");
            $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $recentLines = array_slice($lines, -20);
                foreach ($recentLines as $line) {
                    if (strpos($line, 'handleLanguageSelection') !== false ||
                        strpos($line, 'sendMessage') !== false) {
                        $this->line(trim($line));
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("测试失败: " . $e->getMessage());
            $this->error("错误详情: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
