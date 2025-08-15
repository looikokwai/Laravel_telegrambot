<?php

namespace App\Services\Telegram\Commands;

// use App\Models\TelegramCommand; // 暂时注释掉，使用硬编码的命令列表
use Illuminate\Support\Facades\Log;

/**
 * Telegram命令工厂类
 */
class TelegramCommandFactory
{
    private array $commandHandlers = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    /**
     * 注册所有命令处理器
     */
    private function registerCommands(): void
    {
        $this->commandHandlers = [
            'start' => StartCommand::class,
        ];
    }

    /**
     * 获取命令处理器
     *
     * @param string $command
     * @return TelegramCommandInterface|null
     */
    public function getCommandHandler(string $command): ?TelegramCommandInterface
    {
        // 移除命令前缀
        $command = ltrim($command, '/');

        // 只允许 start 命令，其他功能通过动态菜单实现
        $allowedCommands = ['start'];

        if (!in_array($command, $allowedCommands)) {
            return null;
        }

        // 检查是否有对应的处理器类
        if (!isset($this->commandHandlers[$command])) {
            return null;
        }

        $handlerClass = $this->commandHandlers[$command];

        try {
            // 使用 Laravel 容器来解析依赖
            return app($handlerClass);
        } catch (\Exception $e) {
            Log::error("Failed to create command handler for {$command}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取所有可用的命令
     *
     * @return array
     */
    public function getAvailableCommands(): array
    {
        // 只返回 start 命令，其他功能通过动态菜单实现
        return [
            ['command' => 'start', 'description' => '开始使用机器人'],
        ];
    }

    /**
     * 检查命令是否存在
     *
     * @param string $command
     * @return bool
     */
    public function hasCommand(string $command): bool
    {
        $command = ltrim($command, '/');
        $allowedCommands = ['start'];

        return in_array($command, $allowedCommands);
    }
}
