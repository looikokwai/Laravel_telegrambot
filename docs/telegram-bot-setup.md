# Telegram Bot 设置指南

## 📋 **环境配置**

### 必需的环境变量

在您的 `.env` 文件中添加以下配置：

```env
# Telegram Bot 配置
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_BOT_ID=your_bot_id_here
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

### 🔧 **获取 Bot ID**

如果您不知道您的 Bot ID，可以使用以下命令获取：

```bash
php artisan telegram:get-bot-id
```

这个命令会：
- 连接到 Telegram API
- 获取您的 Bot 信息
- 显示 Bot ID 和其他详细信息
- 提供正确的配置格式

### ⚠️ **常见错误解决**

#### 1. "Bad Request: invalid user_id specified" 错误

**原因**：Bot ID 未配置或配置错误

**解决方案**：
1. 运行 `php artisan telegram:get-bot-id` 获取正确的 Bot ID
2. 在 `.env` 文件中添加 `TELEGRAM_BOT_ID=your_bot_id`
3. 清除配置缓存：`php artisan config:clear`

#### 2. "Bot 不是频道管理员" 错误

**原因**：Bot 没有被添加为频道管理员

**解决方案**：
1. 将 Bot 添加到目标频道
2. 确保 Bot 具有管理员权限
3. 确保 Bot 具有发布消息权限

#### 3. "Bot 不是群组成员" 错误

**原因**：Bot 没有被添加到群组

**解决方案**：
1. 将 Bot 添加到目标群组
2. 确保 Bot 具有发送消息权限

#### 4. "Argument #1 ($member) must be of type array, Telegram\Bot\Objects\ChatMember given" 错误

**原因**：Telegram SDK 返回的是对象而不是数组

**解决方案**：
1. 确保已更新到最新版本的代码
2. 清除配置缓存：`php artisan config:clear`
3. 重启队列处理器：`php artisan queue:restart`

#### 5. 其他 Telegram API 错误

**常见原因**：
- Bot Token 无效或过期
- 网络连接问题
- API 限制或配额超限

**解决方案**：
1. 检查 Bot Token 是否正确
2. 检查网络连接
3. 查看 Telegram API 状态
4. 检查日志文件获取详细错误信息

## 🚀 **快速开始**

1. **配置环境变量**
   ```bash
   # 复制环境配置文件
   cp .env.example .env
   
   # 编辑配置文件
   nano .env
   ```

2. **获取 Bot 信息**
   ```bash
   php artisan telegram:get-bot-id
   ```

3. **设置 Webhook**
   ```bash
   php artisan telegram:set-webhook
   ```

4. **运行迁移**
   ```bash
   php artisan migrate
   ```

5. **启动队列处理器**
   ```bash
   php artisan queue:work
   ```

## 📝 **配置验证**

运行以下命令验证配置是否正确：

```bash
# 验证 Bot 配置
php artisan telegram:get-bot-id

# 验证 Webhook 设置
php artisan telegram:get-webhook-info

# 测试 Bot 连接
php artisan telegram:test-connection
```

## 🔍 **故障排除**

### 检查日志文件

如果遇到问题，请检查以下日志文件：

```bash
# Laravel 日志
tail -f storage/logs/laravel.log

# Telegram 相关日志
tail -f storage/logs/telegram.log
```

### 常见问题

1. **Bot Token 无效**
   - 检查 Token 是否正确
   - 确保没有多余的空格或字符

2. **Webhook 设置失败**
   - 确保域名可以公网访问
   - 检查 SSL 证书是否有效
   - 确保 Webhook URL 路径正确

3. **权限问题**
   - 确保 Bot 在频道/群组中具有适当权限
   - 检查 Bot 是否被管理员移除

## 📞 **获取帮助**

如果遇到其他问题，请：

1. 检查日志文件获取详细错误信息
2. 运行诊断命令：`php artisan telegram:diagnose`
3. 查看 Telegram Bot API 文档
4. 联系技术支持
