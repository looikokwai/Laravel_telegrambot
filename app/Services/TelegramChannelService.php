<?php

namespace App\Services;

use App\Models\TelegramChannel;
use App\Models\TelegramChannelMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\FileUpload\InputFile;

class TelegramChannelService
{
    /**
     * 添加频道
     */
    public function addChannel(string $channelId, array $data = []): array
    {
        try {
            // 验证频道权限
            $validation = $this->validateChannelPermission($channelId);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'code' => $validation['code']
                ];
            }

            // 检查频道是否已存在
            $existingChannel = TelegramChannel::where('channel_id', $channelId)->first();
            if ($existingChannel) {
                return [
                    'success' => false,
                    'error' => '频道已存在',
                    'code' => 'CHANNEL_EXISTS'
                ];
            }

            // 创建频道记录
            $channel = TelegramChannel::create([
                'channel_id' => $channelId,
                'title' => $validation['chat']['title'],
                'description' => $validation['chat']['description'] ?? null,
                'type' => $validation['chat']['type'],
                'username' => $validation['chat']['username'] ?? null,
                'subscriber_count' => $validation['chat']['member_count'] ?? 0,
                'is_public' => !empty($validation['chat']['username']),
                'is_active' => true,
                'permissions' => $validation['permissions'],
                'last_activity' => now()
            ]);

            return [
                'success' => true,
                'channel' => $channel,
                'message' => '频道添加成功'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add channel: ' . $e->getMessage(), [
                'channel_id' => $channelId,
                'error' => $e
            ]);

            return [
                'success' => false,
                'error' => '添加频道失败: ' . $e->getMessage(),
                'code' => 'ADD_FAILED'
            ];
        }
    }

    /**
     * 验证频道权限
     */
    public function validateChannelPermission(string $channelId): array
    {
        try {
            // 验证频道 ID 格式
            if (!$this->isValidChannelId($channelId)) {
                return [
                    'valid' => false,
                    'error' => '频道 ID 格式不正确',
                    'code' => 'INVALID_CHANNEL_ID'
                ];
            }

            // 获取 Bot ID
            $botId = config('telegram.bot.id');
            if (!$botId) {
                return [
                    'valid' => false,
                    'error' => 'Bot ID 未配置，请在 .env 文件中设置 TELEGRAM_BOT_ID',
                    'code' => 'BOT_ID_NOT_CONFIGURED'
                ];
            }

            // 检查频道是否存在
            $chat = Telegram::getChat(['chat_id' => $channelId]);
            $chat = $this->convertTelegramObjectToArray($chat);

            if ($chat['type'] !== 'channel') {
                return [
                    'valid' => false,
                    'error' => '不是有效的频道',
                    'code' => 'NOT_A_CHANNEL'
                ];
            }

            // 检查 Bot 管理员权限
            $member = Telegram::getChatMember([
                'chat_id' => $channelId,
                'user_id' => $botId
            ]);
            $member = $this->convertTelegramObjectToArray($member);

            if ($member['status'] !== 'administrator') {
                return [
                    'valid' => false,
                    'error' => 'Bot 不是频道管理员',
                    'code' => 'BOT_NOT_ADMIN'
                ];
            }

            // 检查发布权限
            if (!($member['can_post_messages'] ?? false)) {
                return [
                    'valid' => false,
                    'error' => 'Bot 没有发布消息权限',
                    'code' => 'NO_POST_PERMISSION'
                ];
            }

            return [
                'valid' => true,
                'chat' => $chat,
                'member' => $member,
                'permissions' => $this->extractChannelPermissions($member)
            ];

        } catch (\Exception $e) {
            Log::error('Channel permission validation failed: ' . $e->getMessage(), [
                'channel_id' => $channelId,
                'bot_id' => config('telegram.bot.id'),
                'error' => $e
            ]);

            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'code' => 'API_ERROR'
            ];
        }
    }

    /**
     * 获取频道列表
     */
    public function getChannels(array $filters = []): Collection
    {
        $query = TelegramChannel::query();

        // 应用过滤器
        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['public'])) {
            $query->where('is_public', $filters['public']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('username', 'like', '%' . $filters['search'] . '%');
            });
        }

        // 排序
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->get();
    }

    /**
     * 更新频道信息
     */
    public function updateChannel(int $id, array $data): bool
    {
        try {
            $channel = TelegramChannel::findOrFail($id);
            $channel->update($data);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update channel: ' . $e->getMessage(), [
                'channel_id' => $id,
                'data' => $data,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * 删除频道
     */
    public function deleteChannel(int $id): bool
    {
        try {
            $channel = TelegramChannel::findOrFail($id);
            $channel->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete channel: ' . $e->getMessage(), [
                'channel_id' => $id,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * 发布消息到频道
     */
    public function publishMessage(int $channelId, string $message, array $options = []): array
    {
        try {
            $channel = TelegramChannel::findOrFail($channelId);

            if (!$channel->is_active) {
                return [
                    'success' => false,
                    'error' => '频道已停用',
                    'code' => 'CHANNEL_INACTIVE'
                ];
            }

            // 创建消息记录
            $messageRecord = TelegramChannelMessage::create([
                'channel_id' => $channelId,
                'message_text' => $message,
                'image_path' => $options['image_path'] ?? null,
                'keyboard' => $options['keyboard'] ?? null,
                'sent_by' => 'admin',
                'status' => 'pending'
            ]);

            // 发布消息
            if (!empty($options['image_path'])) {
                // 发送带图片的消息
                $imagePath = Storage::path($options['image_path']);
                $params = [
                    'chat_id' => $channel->channel_id,
                    'photo' => InputFile::create($imagePath),
                    'caption' => $message,
                    'parse_mode' => 'HTML'
                ];

                if (!empty($options['keyboard'])) {
                    // 转换键盘格式为 Telegram 内联键盘格式
                    $inlineKeyboard = $this->formatInlineKeyboard($options['keyboard']);
                    if (!empty($inlineKeyboard)) {
                        $params['reply_markup'] = json_encode([
                            'inline_keyboard' => $inlineKeyboard
                        ]);
                    }
                }

                $response = Telegram::sendPhoto($params);
            } else {
                // 发送纯文本消息
                $params = [
                    'chat_id' => $channel->channel_id,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];

                if (!empty($options['keyboard'])) {
                    // 转换键盘格式为 Telegram 内联键盘格式
                    $inlineKeyboard = $this->formatInlineKeyboard($options['keyboard']);
                    if (!empty($inlineKeyboard)) {
                        $params['reply_markup'] = json_encode([
                            'inline_keyboard' => $inlineKeyboard
                        ]);
                    }
                }

                $response = Telegram::sendMessage($params);
            }

            // 更新消息记录
            $messageRecord->markAsSent($response['message_id']);

            // 更新频道最后活跃时间
            $channel->updateLastActivity();

            return [
                'success' => true,
                'message_id' => $response['message_id'],
                'message_record' => $messageRecord
            ];

        } catch (\Exception $e) {
            Log::error('Failed to publish message to channel: ' . $e->getMessage(), [
                'channel_id' => $channelId,
                'message' => $message,
                'error' => $e
            ]);

            if (isset($messageRecord)) {
                $messageRecord->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'PUBLISH_FAILED'
            ];
        }
    }

    /**
     * 获取频道统计
     */
    public function getChannelStats(int $channelId): array
    {
        try {
            $channel = TelegramChannel::findOrFail($channelId);

            $totalMessages = $channel->messages()->count();
            $successMessages = $channel->messages()->where('status', 'sent')->count();
            $failedMessages = $channel->messages()->where('status', 'failed')->count();
            $recentMessages = $channel->messages()->recent(7)->count();

            return [
                'channel' => $channel,
                'stats' => [
                    'total_messages' => $totalMessages,
                    'success_messages' => $successMessages,
                    'failed_messages' => $failedMessages,
                    'recent_messages' => $recentMessages,
                    'success_rate' => $totalMessages > 0 ? round(($successMessages / $totalMessages) * 100, 2) : 0
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get channel stats: ' . $e->getMessage(), [
                'channel_id' => $channelId,
                'error' => $e
            ]);

            return [
                'channel' => null,
                'stats' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 验证频道 ID 格式
     */
    private function isValidChannelId(string $channelId): bool
    {
        // 数字 ID 格式
        if (preg_match('/^-?\d+$/', $channelId)) {
            return true;
        }

        // 用户名格式
        if (preg_match('/^@[a-zA-Z0-9_]{5,}$/', $channelId)) {
            return true;
        }

        return false;
    }

    /**
     * 将 Telegram API 返回的对象转换为数组
     */
    private function convertTelegramObjectToArray($object): array
    {
        if (is_array($object)) {
            return $object;
        }

        if (is_object($object)) {
            if (method_exists($object, 'toArray')) {
                return $object->toArray();
            }
            return (array) $object;
        }

        return [];
    }

    /**
     * 提取频道权限信息
     */
    private function extractChannelPermissions($member): array
    {
        // 使用辅助方法转换对象
        $member = $this->convertTelegramObjectToArray($member);

        // 确保 $member 是数组
        if (!is_array($member)) {
            Log::warning('extractChannelPermissions: member is not array or object', [
                'member_type' => gettype($member),
                'member' => $member
            ]);
            return [];
        }

        $permissions = [
            'status' => $member['status'] ?? 'unknown',
            'can_post_messages' => $member['can_post_messages'] ?? false,
            'can_edit_messages' => $member['can_edit_messages'] ?? false,
            'can_delete_messages' => $member['can_delete_messages'] ?? false,
            'can_restrict_members' => $member['can_restrict_members'] ?? false,
            'can_invite_users' => $member['can_invite_users'] ?? false,
            'can_promote_members' => $member['can_promote_members'] ?? false,
            'can_change_info' => $member['can_change_info'] ?? false,
            'can_post_stories' => $member['can_post_stories'] ?? false,
            'can_edit_stories' => $member['can_edit_stories'] ?? false,
            'can_delete_stories' => $member['can_delete_stories'] ?? false,
        ];

        return $permissions;
    }

    /**
     * 格式化内联键盘为 Telegram 格式
     */
    private function formatInlineKeyboard(array $keyboard): array
    {
        if (empty($keyboard)) {
            return [];
        }

        $inlineKeyboard = [];
        
        foreach ($keyboard as $row) {
            if (!is_array($row)) {
                continue;
            }
            
            $keyboardRow = [];
            foreach ($row as $button) {
                if (!is_array($button) || empty($button['text'])) {
                    continue;
                }
                
                $inlineButton = ['text' => $button['text']];
                
                if (!empty($button['url'])) {
                    $inlineButton['url'] = $button['url'];
                } elseif (!empty($button['callback_data'])) {
                    $inlineButton['callback_data'] = $button['callback_data'];
                }
                
                $keyboardRow[] = $inlineButton;
            }
            
            if (!empty($keyboardRow)) {
                $inlineKeyboard[] = $keyboardRow;
            }
        }
        
        return $inlineKeyboard;
    }
}
