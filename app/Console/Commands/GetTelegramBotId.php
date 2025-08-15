<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class GetTelegramBotId extends Command
{
    /**
     * 命令名称
     */
    protected $signature = 'telegram:get-bot-id';

    /**
     * 命令描述
     */
    protected $description = '获取 Telegram Bot ID 并显示配置信息';

    /**
     * 执行命令
     */
    public function handle()
    {
        $this->info('正在获取 Telegram Bot 信息...');

        try {
            // 获取 Bot 信息
            $bot = Telegram::getMe();

            $this->info('✅ Bot 信息获取成功！');
            $this->newLine();

            // 显示 Bot 信息
            $this->table(
                ['字段', '值'],
                [
                    ['Bot ID', $bot['id']],
                    ['Bot 用户名', '@' . $bot['username']],
                    ['Bot 名称', $bot['first_name']],
                    ['是否支持内联模式', $bot['supports_inline_queries'] ? '是' : '否'],
                ]
            );

            $this->newLine();
            $this->info('📝 请在您的 .env 文件中添加以下配置：');
            $this->line("TELEGRAM_BOT_ID={$bot['id']}");

            $this->newLine();
            $this->warn('⚠️  请确保您已经设置了 TELEGRAM_BOT_TOKEN');

        } catch (\Exception $e) {
            $this->error('❌ 获取 Bot 信息失败：' . $e->getMessage());
            $this->newLine();
            $this->info('请检查以下配置：');
            $this->line('1. 确保在 .env 文件中设置了 TELEGRAM_BOT_TOKEN');
            $this->line('2. 确保 Bot Token 是有效的');
            $this->line('3. 确保网络连接正常');

            return 1;
        }

        return 0;
    }
}
