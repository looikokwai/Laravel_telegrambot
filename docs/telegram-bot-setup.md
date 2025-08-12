# Telegram Bot 设置和使用指南

## 📋 目录
- [环境配置](#环境配置)
- [必需命令](#必需命令)
- [测试Bot功能](#测试bot功能)
- [发送消息方法](#发送消息方法)
- [多语言支持](#多语言支持)
- [API接口](#api接口)
- [常见问题](#常见问题)

## 🔧 环境配置

### 1. 获取Bot Token
1. 在Telegram中找到 [@BotFather](https://t.me/botfather)
2. 发送 `/newbot` 创建新Bot
3. 按提示设置Bot名称和用户名
4. 获取Bot Token并保存

### 2. 配置环境变量
在 `.env` 文件中添加：
```env
TELEGRAM_BOT_TOKEN=你的Bot_Token
```

## ⚡ 必需命令

### 安装依赖
```bash
# 安装Telegram Bot SDK
composer require irazasyed/telegram-bot-sdk

# 发布配置文件
php artisan vendor:publish --tag=telegram-config
```

### 数据库迁移
```bash
# 运行迁移创建telegram_users表
php artisan migrate
```

### 设置Webhook（生产环境）
```bash
# 自动使用APP_URL设置webhook
php artisan telegram:set-webhook

# 或指定自定义URL
php artisan telegram:set-webhook https://telegram.oneithosting.xyz/telegram/webhook
```

### 启动队列处理器
```bash
# 启动队列工作进程（必须运行）
php artisan queue:work

# 或者在后台运行
php artisan queue:work --daemon
```

### 开发环境启动
```bash
# 启动完整开发环境（包含队列）
composer run dev
```

## 🧪 测试Bot功能

### 1. 基本测试流程
1. **找到你的Bot**
   - 在Telegram搜索你的Bot用户名
   - 点击开始对话

2. **测试基本命令**
   ```
   /start    - 测试欢迎消息
   /help     - 测试帮助信息
   ```

3. **发送普通消息**
   - 发送任意文本消息
   - 检查是否收到自动回复

4. **检查数据库**
   ```bash
   # 查看保存的用户信息
   php artisan tinker
   >>> App\Models\TelegramUser::all()
   ```

### 2. 验证Webhook设置
```bash
# 检查webhook状态
curl -X GET "https://api.telegram.org/bot你的TOKEN/getWebhookInfo"
```

## 📤 发送消息方法

### 方法1：通过代码发送
```php
use App\Jobs\SendTelegramMessage;
use App\Models\TelegramUser;

// 发送给特定用户
$user = TelegramUser::first();
SendTelegramMessage::dispatch($user->chat_id, '你好！这是测试消息');

// 群发给所有活跃用户
$activeUsers = TelegramUser::active()->get();
foreach ($activeUsers as $user) {
    SendTelegramMessage::dispatch($user->chat_id, '群发消息内容');
}

// 发送HTML格式消息
SendTelegramMessage::dispatch($chatId, '<b>粗体文本</b>', [
    'parse_mode' => 'HTML'
]);
```

### 方法2：在控制器中使用
```php
use Telegram\Bot\Laravel\Facades\Telegram;

// 直接发送（同步）
Telegram::sendMessage([
    'chat_id' => $chatId,
    'text' => '消息内容'
]);
```

### 方法3：通过Artisan命令
```bash
# 创建自定义命令发送消息
php artisan make:command SendTelegramBroadcast
```

## 🔌 API接口

### 发送消息给特定用户
```bash
POST /telegram/send-message
Content-Type: application/json
Authorization: 需要用户登录

{
    "user_id": 1,
    "message": "Hello from API!"
}
```

### 群发消息
```bash
POST /telegram/broadcast
Content-Type: application/json
Authorization: 需要用户登录

{
    "message": "群发消息内容",
    "target": "active"  // all, active, recent
}
```

### 获取用户列表
```bash
GET /telegram/users
Authorization: 需要用户登录
```

## 🛠️ 高级配置

## 🌍 多语言支持

### 支持的语言
- 🇺🇸 English (`en`)
- 🇨🇳 中文 (`zh`) 
- 🇲🇾 Bahasa Malaysia (`ms`)

### 语言文件结构
```
lang/
├── en/telegram.php
├── zh/telegram.php
└── ms/telegram.php
```

### 使用多语言服务
```php
use App\Services\TelegramLanguageService;

// 为特定用户获取翻译
$message = TelegramLanguageService::transForUser($userId, 'welcome.title');

// 检查用户是否已选择语言
$hasSelected = TelegramLanguageService::hasUserSelectedLanguage($userId);

// 设置用户语言
TelegramLanguageService::setUserLanguage($userId, 'zh');

// 生成语言选择键盘
$keyboard = TelegramLanguageService::getLanguageKeyboard();
```

### 添加新语言
1. 在 `lang/` 目录创建新语言文件夹
2. 复制 `telegram.php` 文件并翻译
3. 在 `TelegramLanguageService` 中添加语言映射

### 自定义命令处理
在 `TelegramBotController.php` 中添加新命令：
```php
private function handleUserMessage($chatId, $text, $userId)
{
    // 获取用户语言
    $language = TelegramLanguageService::getUserLanguage($userId);
    
    switch (strtolower($text)) {
        case '/start':
            $this->sendWelcomeMessage($chatId, $userId);
            break;
        case '/language':
            $this->sendLanguageSelection($chatId);
            break;
        case '/contact':
            $this->sendContactInfo($chatId, $userId);
            break;
        case '/status':
            $this->sendUserStatus($chatId, $userId);
            break;
        // 添加更多命令...
        default:
            $this->sendDefaultResponse($chatId, $text, $userId);
            break;
    }
}
```

### 配置队列驱动
在 `.env` 中设置：
```env
QUEUE_CONNECTION=database  # 或 redis, sqs 等
```

### 消息模板系统
```php
// 创建消息模板
class MessageTemplate
{
    public static function welcome($name)
    {
        return "🎉 欢迎 {$name}！\n\n感谢使用我们的服务...";
    }
    
    public static function notification($title, $content)
    {
        return "📢 <b>{$title}</b>\n\n{$content}";
    }
}
```

## ❓ 常见问题

### Q: Webhook设置失败
**A:** 检查以下几点：
- 确保URL是HTTPS（生产环境）
- 检查防火墙设置
- 验证SSL证书有效性

### Q: 消息发送失败
**A:** 可能原因：
- Bot Token错误
- 用户阻止了Bot
- 队列处理器未运行
- 网络连接问题

### Q: 用户信息未保存
**A:** 检查：
- 数据库连接是否正常
- 迁移是否已运行
- Webhook是否正确接收消息

### Q: 队列消息堆积
**A:** 解决方法：
```bash
# 清空失败的队列任务
php artisan queue:flush

# 重启队列处理器
php artisan queue:restart
```

### Q: 开发环境测试
**A:** 使用ngrok暴露本地服务：
```bash
# 安装ngrok后
ngrok http 8000

# 使用ngrok提供的HTTPS URL设置webhook
php artisan telegram:set-webhook https://abc123.ngrok.io/telegram/webhook
```

## 📊 监控和日志

### 查看队列状态
```bash
# 查看队列任务
php artisan queue:monitor

# 查看失败任务
php artisan queue:failed
```

### 日志文件位置
- Laravel日志: `storage/logs/laravel.log`
- Telegram相关日志会标记为 `Telegram webhook` 或 `Telegram message`

### 性能优化
```bash
# 使用多个队列工作进程
php artisan queue:work --queue=telegram --processes=3

# 设置内存限制
php artisan queue:work --memory=512
```

## 🔐 安全建议

1. **保护Webhook端点**
   - 验证请求来源
   - 使用HTTPS
   - 设置访问限制

2. **Token安全**
   - 不要在代码中硬编码Token
   - 定期轮换Token
   - 使用环境变量

3. **用户数据保护**
   - 遵循GDPR等数据保护法规
   - 提供数据删除功能
   - 加密敏感信息

---

## 📞 技术支持

如遇到问题，请检查：
1. Laravel日志文件
2. 队列处理器状态
3. Telegram API响应
4. 网络连接状态

更多信息请参考：
- [快速开始指南](telegram-bot-quickstart.md)
- [多语言支持指南](telegram-bot-multilingual.md)
- [API参考文档](telegram-bot-api.md)
- [Telegram Bot API官方文档](https://core.telegram.org/bots/api)
- [Laravel队列文档](https://laravel.com/docs/queues)
