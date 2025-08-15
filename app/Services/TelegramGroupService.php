<?php

namespace App\Services;

use App\Models\TelegramGroup;
use App\Models\TelegramGroupMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\FileUpload\InputFile;

class TelegramGroupService
{
    /**
     * 添加群组
     */
    public function addGroup(string $groupId, array $data = []): array
    {
        try {
            // 验证群组权限
            $validation = $this->validateGroupPermission($groupId);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'code' => $validation['code']
                ];
            }

            // 检查群组是否已存在
            $existingGroup = TelegramGroup::where('group_id', $groupId)->first();
            if ($existingGroup) {
                return [
                    'success' => false,
                    'error' => '群组已存在',
                    'code' => 'GROUP_EXISTS'
                ];
            }

            // 创建群组记录
            $group = TelegramGroup::create([
                'group_id' => $groupId,
                'title' => $validation['chat']['title'],
                'description' => $validation['chat']['description'] ?? null,
                'type' => $validation['chat']['type'],
                'username' => $validation['chat']['username'] ?? null,
                'member_count' => $validation['chat']['member_count'] ?? 0,
                'is_active' => true,
                'permissions' => $validation['permissions'],
                'last_activity' => now()
            ]);

            return [
                'success' => true,
                'group' => $group,
                'message' => '群组添加成功'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add group: ' . $e->getMessage(), [
                'group_id' => $groupId,
                'error' => $e
            ]);

            return [
                'success' => false,
                'error' => '添加群组失败: ' . $e->getMessage(),
                'code' => 'ADD_FAILED'
            ];
        }
    }

    /**
     * 验证群组权限
     */
    public function validateGroupPermission(string $groupId): array
    {
        try {
            // 验证群组 ID 格式
            if (!$this->isValidGroupId($groupId)) {
                return [
                    'valid' => false,
                    'error' => '群组 ID 格式不正确',
                    'code' => 'INVALID_GROUP_ID'
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

            // 检查群组是否存在
            $chat = Telegram::getChat(['chat_id' => $groupId]);
            $chat = $this->convertTelegramObjectToArray($chat);

            if (!in_array($chat['type'], ['group', 'supergroup'])) {
                return [
                    'valid' => false,
                    'error' => '不是有效的群组',
                    'code' => 'NOT_A_GROUP'
                ];
            }

            // 检查 Bot 权限
            $member = Telegram::getChatMember([
                'chat_id' => $groupId,
                'user_id' => $botId
            ]);
            $member = $this->convertTelegramObjectToArray($member);

            if (!in_array($member['status'], ['member', 'administrator'])) {
                return [
                    'valid' => false,
                    'error' => 'Bot 不是群组成员',
                    'code' => 'BOT_NOT_MEMBER'
                ];
            }

            // 检查发送消息权限
            if ($member['status'] === 'member' && !($member['can_send_messages'] ?? true)) {
                return [
                    'valid' => false,
                    'error' => 'Bot 没有发送消息权限',
                    'code' => 'NO_SEND_PERMISSION'
                ];
            }

            return [
                'valid' => true,
                'chat' => $chat,
                'member' => $member,
                'permissions' => $this->extractPermissions($member)
            ];

        } catch (\Exception $e) {
            Log::error('Group permission validation failed: ' . $e->getMessage(), [
                'group_id' => $groupId,
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
     * 获取群组列表
     */
    public function getGroups(array $filters = []): Collection
    {
        $query = TelegramGroup::query();

        // 应用过滤器
        if (isset($filters['active'])) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
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
     * 更新群组信息
     */
    public function updateGroup(int $id, array $data): bool
    {
        try {
            $group = TelegramGroup::findOrFail($id);
            $group->update($data);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update group: ' . $e->getMessage(), [
                'group_id' => $id,
                'data' => $data,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * 删除群组
     */
    public function deleteGroup(int $id): bool
    {
        try {
            $group = TelegramGroup::findOrFail($id);
            $group->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete group: ' . $e->getMessage(), [
                'group_id' => $id,
                'error' => $e
            ]);
            return false;
        }
    }

    /**
     * 发送消息到群组
     */
    public function sendMessage(int $groupId, string $message, array $options = []): array
    {
        try {
            $group = TelegramGroup::findOrFail($groupId);

            if (!$group->is_active) {
                return [
                    'success' => false,
                    'error' => '群组已停用',
                    'code' => 'GROUP_INACTIVE'
                ];
            }

            // 创建消息记录
            $messageRecord = TelegramGroupMessage::create([
                'group_id' => $groupId,
                'message_text' => $message,
                'image_path' => $options['image_path'] ?? null,
                'keyboard' => $options['keyboard'] ?? null,
                'sent_by' => 'admin',
                'status' => 'pending'
            ]);

            // 发送消息
            if (!empty($options['image_path'])) {
                // 发送带图片的消息
                $imagePath = Storage::path($options['image_path']);
                $params = [
                    'chat_id' => $group->group_id,
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
                    'chat_id' => $group->group_id,
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

            // 更新群组最后活跃时间
            $group->updateLastActivity();

            return [
                'success' => true,
                'message_id' => $response['message_id'],
                'message_record' => $messageRecord
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send message to group: ' . $e->getMessage(), [
                'group_id' => $groupId,
                'message' => $message,
                'error' => $e
            ]);

            if (isset($messageRecord)) {
                $messageRecord->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'SEND_FAILED'
            ];
        }
    }

    /**
     * 获取群组统计
     */
    public function getGroupStats(int $groupId): array
    {
        try {
            $group = TelegramGroup::findOrFail($groupId);

            $totalMessages = $group->messages()->count();
            $successMessages = $group->messages()->where('status', 'sent')->count();
            $failedMessages = $group->messages()->where('status', 'failed')->count();
            $recentMessages = $group->messages()->recent(7)->count();

            return [
                'group' => $group,
                'stats' => [
                    'total_messages' => $totalMessages,
                    'success_messages' => $successMessages,
                    'failed_messages' => $failedMessages,
                    'recent_messages' => $recentMessages,
                    'success_rate' => $totalMessages > 0 ? round(($successMessages / $totalMessages) * 100, 2) : 0
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get group stats: ' . $e->getMessage(), [
                'group_id' => $groupId,
                'error' => $e
            ]);

            return [
                'group' => null,
                'stats' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 验证群组 ID 格式
     */
    private function isValidGroupId(string $groupId): bool
    {
        // 数字 ID 格式
        if (preg_match('/^-?\d+$/', $groupId)) {
            return true;
        }

        // 用户名格式
        if (preg_match('/^@[a-zA-Z0-9_]{5,}$/', $groupId)) {
            return true;
        }

        // 邀请链接格式
        if (preg_match('/^https:\/\/t\.me\/joinchat\/[a-zA-Z0-9_-]+$/', $groupId)) {
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
     * 提取权限信息
     */
    private function extractPermissions($member): array
    {
        // 使用辅助方法转换对象
        $member = $this->convertTelegramObjectToArray($member);

        // 确保 $member 是数组
        if (!is_array($member)) {
            Log::warning('extractPermissions: member is not array or object', [
                'member_type' => gettype($member),
                'member' => $member
            ]);
            return [];
        }

        $permissions = [
            'status' => $member['status'] ?? 'unknown',
            'can_send_messages' => $member['can_send_messages'] ?? false,
            'can_send_media_messages' => $member['can_send_media_messages'] ?? false,
            'can_send_polls' => $member['can_send_polls'] ?? false,
            'can_send_other_messages' => $member['can_send_other_messages'] ?? false,
            'can_add_web_page_previews' => $member['can_add_web_page_previews'] ?? false,
            'can_change_info' => $member['can_change_info'] ?? false,
            'can_invite_users' => $member['can_invite_users'] ?? false,
            'can_pin_messages' => $member['can_pin_messages'] ?? false,
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
