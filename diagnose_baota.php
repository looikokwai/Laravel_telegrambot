<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// 启动 Laravel 应用
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== 宝塔面板环境 Telegram Bot 诊断报告 ===\n\n";

// 1. 检查宝塔面板相关配置
echo "1. 宝塔面板环境检查:\n";
echo "PHP 版本: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "当前用户: " . get_current_user() . "\n";
echo "当前进程用户: " . posix_getpwuid(posix_geteuid())['name'] . "\n";
echo "当前工作目录: " . getcwd() . "\n\n";

// 2. 检查文件权限和所有者
echo "2. 文件权限检查:\n";
$storagePath = storage_path();
echo "storage 目录: " . $storagePath . "\n";
echo "storage 目录权限: " . substr(sprintf('%o', fileperms($storagePath)), -4) . "\n";
echo "storage 目录所有者: " . posix_getpwuid(fileowner($storagePath))['name'] . "\n";
echo "storage 目录可写: " . (is_writable($storagePath) ? '✅ 是' : '❌ 否') . "\n";

$logsPath = storage_path('logs');
echo "logs 目录: " . $logsPath . "\n";
echo "logs 目录权限: " . substr(sprintf('%o', fileperms($logsPath)), -4) . "\n";
echo "logs 目录所有者: " . posix_getpwuid(fileowner($logsPath))['name'] . "\n";
echo "logs 目录可写: " . (is_writable($logsPath) ? '✅ 是' : '❌ 否') . "\n";

$appPath = storage_path('app');
echo "app 目录: " . $appPath . "\n";
echo "app 目录权限: " . substr(sprintf('%o', fileperms($appPath)), -4) . "\n";
echo "app 目录所有者: " . posix_getpwuid(fileowner($appPath))['name'] . "\n";
echo "app 目录可写: " . (is_writable($appPath) ? '✅ 是' : '❌ 否') . "\n\n";

// 3. 检查宝塔面板的 PHP 配置
echo "3. PHP 配置检查:\n";
echo "disable_functions: " . ini_get('disable_functions') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ 开启' : '❌ 关闭') . "\n";
echo "curl 扩展: " . (extension_loaded('curl') ? '✅ 已安装' : '❌ 未安装') . "\n";
echo "openssl 扩展: " . (extension_loaded('openssl') ? '✅ 已安装' : '❌ 未安装') . "\n";
echo "json 扩展: " . (extension_loaded('json') ? '✅ 已安装' : '❌ 未安装') . "\n\n";

// 4. 检查网络连接和防火墙
echo "4. 网络连接检查:\n";
$testUrls = [
    'https://api.telegram.org' => 'Telegram API',
    'https://httpbin.org/get' => 'HTTP 测试',
    'https://www.google.com' => 'Google 连接测试'
];

foreach ($testUrls as $url => $description) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Laravel-Telegram-Bot/1.0'
        ]
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        echo "❌ {$description}: 连接失败\n";
    } else {
        echo "✅ {$description}: 连接成功\n";
    }
}
echo "\n";

// 5. 检查环境变量
echo "5. 环境变量检查:\n";
echo "TELEGRAM_BOT_TOKEN: " . (env('TELEGRAM_BOT_TOKEN') ? '已设置' : '❌ 未设置') . "\n";
echo "TELEGRAM_WEBHOOK_URL: " . (env('TELEGRAM_WEBHOOK_URL') ? '已设置' : '❌ 未设置') . "\n";
echo "APP_ENV: " . env('APP_ENV', '未设置') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "DB_HOST: " . env('DB_HOST', '未设置') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE', '未设置') . "\n\n";

// 6. 检查数据库连接
echo "6. 数据库连接检查:\n";
try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    echo "✅ 数据库连接成功\n";

    // 检查表
    $stmt = $pdo->query("SELECT COUNT(*) FROM telegram_users");
    $count = $stmt->fetchColumn();
    echo "telegram_users 表记录数: {$count}\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM telegram_languages");
    $count = $stmt->fetchColumn();
    echo "telegram_languages 表记录数: {$count}\n";

} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. 检查 Telegram API 连接
echo "7. Telegram API 连接检查:\n";
try {
    $token = env('TELEGRAM_BOT_TOKEN');
    if (!$token) {
        echo "❌ TELEGRAM_BOT_TOKEN 未设置\n";
    } else {
        $url = "https://api.telegram.org/bot{$token}/getMe";
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Laravel-Telegram-Bot/1.0'
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        if ($response === false) {
            echo "❌ 无法连接到 Telegram API\n";
        } else {
            $data = json_decode($response, true);
            if ($data && isset($data['ok']) && $data['ok']) {
                echo "✅ Telegram API 连接成功\n";
                echo "Bot 名称: " . $data['result']['first_name'] . "\n";
                echo "Bot 用户名: @" . $data['result']['username'] . "\n";
            } else {
                echo "❌ Telegram API 响应错误: " . json_encode($data) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Telegram API 连接异常: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. 检查宝塔面板特有的限制
echo "8. 宝塔面板特有检查:\n";

// 检查是否在宝塔面板环境中
$isBaota = false;
if (file_exists('/www/server/panel')) {
    $isBaota = true;
    echo "✅ 检测到宝塔面板环境\n";
} else {
    echo "⚠️ 未检测到宝塔面板环境\n";
}

// 检查 PHP 进程用户
$currentUser = posix_getpwuid(posix_geteuid())['name'];
echo "当前 PHP 进程用户: {$currentUser}\n";

// 检查网站目录权限
$webRoot = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
echo "网站根目录: {$webRoot}\n";
echo "网站根目录权限: " . substr(sprintf('%o', fileperms($webRoot)), -4) . "\n";
echo "网站根目录所有者: " . posix_getpwuid(fileowner($webRoot))['name'] . "\n\n";

// 9. 检查日志文件
echo "9. 日志文件检查:\n";
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    echo "今日日志文件: {$logFile}\n";
    echo "日志文件大小: " . filesize($logFile) . " 字节\n";
    echo "日志文件权限: " . substr(sprintf('%o', fileperms($logFile)), -4) . "\n";
    echo "日志文件所有者: " . posix_getpwuid(fileowner($logFile))['name'] . "\n";

    $lines = file($logFile);
    $recentLines = array_slice($lines, -5);
    echo "最近 5 行日志:\n";
    foreach ($recentLines as $line) {
        echo trim($line) . "\n";
    }
} else {
    echo "今日日志文件不存在: {$logFile}\n";
}
echo "\n";

// 10. 宝塔面板建议
echo "10. 宝塔面板配置建议:\n";
echo "📋 如果发现问题，请检查以下宝塔面板设置:\n";
echo "1. 网站设置 -> 配置文件 -> 确保 PHP 版本正确\n";
echo "2. 安全 -> 防火墙 -> 确保 80/443 端口开放\n";
echo "3. 软件商店 -> PHP 设置 -> 确保必要扩展已安装\n";
echo "4. 文件 -> 权限设置 -> 确保网站目录权限正确\n";
echo "5. 网站设置 -> 伪静态 -> 确保 Laravel 规则正确\n";
echo "6. 安全 -> 禁用函数 -> 确保未禁用必要函数\n\n";

echo "=== 诊断完成 ===\n";
echo "💡 如果发现问题，请根据上述建议进行相应配置调整。\n";
