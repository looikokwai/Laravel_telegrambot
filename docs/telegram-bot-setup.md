# Telegram Bot 项目设置指南

## SERVER SETTING
location / {
try_files $uri $uri/ /index.php?$query_string;
}

mbstring
fileinfo
putenv
pcntl_signal
pcntl_signal_dispatch 
proc_open 

## 📋 目录
- [环境配置](#环境配置)
- [项目安装](#项目安装)
- [启动服务](#启动服务)
- [功能测试](#功能测试)
- [API 使用](#api-使用)
- [常见问题](#常见问题)

## 🔧 环境配置

### 1. 获取 Bot Token
1. 在 Telegram 中找到 [@BotFather](https://t.me/botfather)
2. 发送 `/newbot` 创建新 Bot
3. 按提示设置 Bot 名称和用户名
4. 获取 Bot Token 并保存

### 2. 配置环境变量
在 `.env` 文件中添加：
```env
TELEGRAM_BOT_TOKEN=你的Bot_Token
```

## ⚡ 项目安装

### 1. 安装依赖
```bash
# 安装项目依赖
composer install

# 安装 Telegram Bot SDK
composer require irazasyed/telegram-bot-sdk

# 发布配置文件
php artisan vendor:publish --tag=telegram-config
```

### 2. 数据库迁移
```bash
# 运行所有迁移（包括 telegram_users, broadcast_messages 等表）
php artisan migrate

# 包含seeder
php artisan migrate --seed

php artisan migrate:fresh --seed
```

### 3. 设置 Webhook
```bash
# 自动使用 APP_URL 设置 webhook
php artisan telegram:set-webhook

# 或指定自定义 URL
php artisan telegram:set-webhook https://yourdomain.com/telegram/webhook
```

## 🚀 启动服务

### 1. 启动队列处理器
```bash
# 启动单个队列工作进程
php artisan queue:work

# 启动多个 worker 进程（推荐用于生产环境）
# 终端1
php artisan queue:work

# 终端2
php artisan queue:work

# 终端3
php artisan queue:work
```

## 🧪 功能测试

### 1. 测试 Bot 基本功能
1. **找到你的 Bot**
   - 在 Telegram 搜索你的 Bot 用户名
   - 点击开始对话

2. **测试基本命令**
   ```
   /start    - 测试欢迎消息
   ```

3. **检查用户保存**
   ```bash
   # 查看保存的用户信息
   php artisan tinker
   >>> App\Models\TelegramUser::all()
   ```

### 2. 测试广播消息功能
1. **访问广播页面**
   - 打开 `http://yourdomain.com/telegram/broadcast`
   - 或本地开发：`http://localhost:8000/telegram/broadcast`

2. **发送测试广播**
   - 选择目标用户（活跃用户、所有用户等）
   - 输入消息内容
   - 点击发送

3. **查看广播历史**
   - 点击"广播历史"标签
   - 查看发送状态和统计

### 3. 测试用户管理
1. **访问用户管理页面**
   - 打开 `http://yourdomain.com/telegram/users`

2. **查看用户列表**
   - 查看所有 Telegram 用户
   - 检查用户状态（活跃/非活跃）

## 🔌 API 使用

### 1. 广播消息 API
```bash
POST /telegram/broadcast
Content-Type: application/json

{
    "message": "广播消息内容",
    "target": "active",  // all, active, recent, recent_30
    "image": "图片文件（可选）",
    "keyboard": "键盘配置（可选）"
}
```

### 2. 发送消息给特定用户
```bash
POST /telegram/send-message
Content-Type: application/json

{
    "user_id": 1,
    "message": "消息内容"
}
```

### 3. 获取用户列表
```bash
GET /telegram/users/data?per_page=20&search=用户名
```

### 4. 获取广播统计
```bash
GET /telegram/broadcast-stats
```

## ❓ 常见问题

### Q: 广播消息显示"发送给 0 个用户"
**A:** 检查以下几点：
- 数据库中是否有用户数据
- 用户状态是否为活跃
- 目标用户选择是否正确

### Q: 消息发送失败
**A:** 可能原因：
- Bot Token 错误
- 用户阻止了 Bot
- 队列处理器未运行
- 网络连接问题

### Q: 队列消息堆积
**A:** 解决方法：
```bash
# 清空失败的队列任务
php artisan queue:flush

# 重启队列处理器
php artisan queue:restart

# 查看失败任务
php artisan queue:failed
```

### Q: 广播统计不准确
**A:** 检查：
- 队列处理器是否正常运行
- 重试机制是否正常工作
- 缓存是否影响统计

### Q: 多 Worker 配置
**A:** 推荐配置：
- 开发环境：2-3 个 worker
- 生产环境：3-5 个 worker
- 使用 PM2 或 Supervisor 管理

## 📊 监控和日志

### 查看队列状态
```bash
# 查看队列任务
php artisan queue:monitor

# 查看失败任务
php artisan queue:failed

# 重试失败任务
php artisan queue:retry all
```

### 日志文件位置
- Laravel 日志: `storage/logs/laravel.log`
- Telegram 相关日志会标记为 `Telegram webhook` 或 `Telegram message`

### 性能优化建议
```bash
# 使用多个队列工作进程
php artisan queue:work --queue=default --processes=3

# 设置内存限制
php artisan queue:work --memory=512

# 设置超时时间
php artisan queue:work --timeout=60
```

## 🔐 安全建议

1. **保护 Webhook 端点**
   - 确保使用 HTTPS
   - 验证请求来源

2. **Token 安全**
   - 不要在代码中硬编码 Token
   - 使用环境变量存储

3. **用户数据保护**
   - 遵循数据保护法规
   - 提供数据删除功能

---

## 📞 技术支持

如遇到问题，请检查：
1. Laravel 日志文件
2. 队列处理器状态
3. Telegram API 响应
4. 网络连接状态

更多信息请参考：
- [Telegram Bot API 官方文档](https://core.telegram.org/bots/api)
- [Laravel 队列文档](https://laravel.com/docs/queues)
