<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\TelegramUser;
use App\Services\TelegramLanguageService;
use App\Services\TelegramMessageService;
use App\Services\TelegramMenuService;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\Commands\HelpCommand;

class TelegramCommandTest extends TestCase
{
    public function test_start_command_structure()
    {
        // 创建模拟服务
        $menuService = $this->createMock(TelegramMenuService::class);
        $languageService = $this->createMock(TelegramLanguageService::class);
        
        // 创建命令实例
        $startCommand = new StartCommand($menuService, $languageService);
        
        // 验证命令名称和描述
        $this->assertEquals('start', $startCommand->getCommandName());
        $this->assertEquals('开始使用机器人', $startCommand->getDescription());
    }

    public function test_help_command_structure()
    {
        // 创建模拟服务
        $languageService = $this->createMock(TelegramLanguageService::class);
        $messageService = $this->createMock(TelegramMessageService::class);
        
        // 创建命令实例
        $helpCommand = new HelpCommand($languageService, $messageService);
        
        // 验证命令名称和描述
        $this->assertEquals('help', $helpCommand->getCommandName());
        $this->assertEquals('显示帮助信息', $helpCommand->getDescription());
    }

    public function test_commands_implement_interface()
    {
        // 创建模拟服务
        $menuService = $this->createMock(TelegramMenuService::class);
        $languageService = $this->createMock(TelegramLanguageService::class);
        $messageService = $this->createMock(TelegramMessageService::class);
        
        // 创建命令实例
        $startCommand = new StartCommand($menuService, $languageService);
        $helpCommand = new HelpCommand($languageService, $messageService);
        
        // 验证命令实现了正确的接口
        $this->assertInstanceOf('App\\Services\\Telegram\\Commands\\TelegramCommandInterface', $startCommand);
        $this->assertInstanceOf('App\\Services\\Telegram\\Commands\\TelegramCommandInterface', $helpCommand);
    }
}