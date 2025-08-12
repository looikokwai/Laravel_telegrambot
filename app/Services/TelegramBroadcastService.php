<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Jobs\SendTelegramMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TelegramBroadcastService
{
    public function __construct(
        private TelegramUserService $userService
    ) {}

    /**
     * 群发消息给指定目标用户
     */
    public function broadcast(string $message, string $target = 'active', array $options = []): array
    {
        $users = $this->getTargetUsers($target);
        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                SendTelegramMessage::dispatch($user->chat_id, $message, $options);
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to queue broadcast message', [
                    'user_id' => $user->id,
                    'chat_id' => $user->chat_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_users' => $users->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'target' => $target
        ];
    }

    /**
     * 多语言群发消息
     */
    public function broadcastMultilingual(string $messageKey, array $parameters = [], string $target = 'active', array $options = []): array
    {
        $usersByLanguage = $this->getTargetUsersByLanguage($target);
        $totalSent = 0;
        $totalFailed = 0;
        $languageStats = [];

        foreach ($usersByLanguage as $language => $users) {
            $message = TelegramLanguageService::trans($messageKey, $parameters, $language);
            $sentCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                try {
                    SendTelegramMessage::dispatch($user->chat_id, $message, $options);
                    $sentCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error('Failed to queue multilingual broadcast message', [
                        'user_id' => $user->id,
                        'language' => $language,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $languageStats[$language] = [
                'users' => $users->count(),
                'sent' => $sentCount,
                'failed' => $failedCount
            ];

            $totalSent += $sentCount;
            $totalFailed += $failedCount;
        }

        return [
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'target' => $target,
            'language_stats' => $languageStats
        ];
    }

    /**
     * 个性化群发消息
     */
    public function broadcastPersonalized(string $messageKey, callable $getParameters = null, string $target = 'active', array $options = []): array
    {
        $users = $this->getTargetUsers($target);
        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                $parameters = $getParameters ? $getParameters($user) : [];
                $message = TelegramLanguageService::transForUser(
                    $user->telegram_user_id,
                    $messageKey,
                    $parameters
                );

                SendTelegramMessage::dispatch($user->chat_id, $message, $options);
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to queue personalized broadcast message', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_users' => $users->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'target' => $target
        ];
    }

    /**
     * 使用自定义模板的个性化群发消息
     */
    public function broadcastCustomTemplate(string $template, callable $getParameters = null, string $target = 'active', array $options = []): array
    {
        $users = $this->getTargetUsers($target);
        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            try {
                $parameters = $getParameters ? $getParameters($user) : [];
                
                // 替换模板中的参数
                $message = $this->replaceTemplateParameters($template, $parameters);

                SendTelegramMessage::dispatch($user->chat_id, $message, $options);
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to queue custom template broadcast message', [
                    'user_id' => $user->id,
                    'template' => $template,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_users' => $users->count(),
            'sent' => $sentCount,
            'failed' => $failedCount,
            'target' => $target,
            'template_used' => 'custom'
        ];
    }

    /**
     * 替换模板中的参数
     */
    private function replaceTemplateParameters(string $template, array $parameters): string
    {
        $message = $template;
        
        foreach ($parameters as $key => $value) {
            $message = str_replace(":{$key}", $value, $message);
        }
        
        return $message;
    }

    /**
     * 按条件筛选用户群发
     */
    public function broadcastToFilteredUsers(string $message, callable $filter, array $options = []): array
    {
        $allUsers = TelegramUser::active()->get();
        $filteredUsers = $allUsers->filter($filter);
        $sentCount = 0;
        $failedCount = 0;

        foreach ($filteredUsers as $user) {
            try {
                SendTelegramMessage::dispatch($user->chat_id, $message, $options);
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to queue filtered broadcast message', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_filtered_users' => $filteredUsers->count(),
            'total_active_users' => $allUsers->count(),
            'sent' => $sentCount,
            'failed' => $failedCount
        ];
    }

    /**
     * 延迟群发消息
     */
    public function scheduleBroadcast(string $message, \DateTime $scheduledAt, string $target = 'active', array $options = []): array
    {
        $users = $this->getTargetUsers($target);
        $scheduledCount = 0;

        foreach ($users as $user) {
            try {
                SendTelegramMessage::dispatch($user->chat_id, $message, $options)
                    ->delay($scheduledAt);
                $scheduledCount++;
            } catch (\Exception $e) {
                Log::error('Failed to schedule broadcast message', [
                    'user_id' => $user->id,
                    'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_users' => $users->count(),
            'scheduled' => $scheduledCount,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'target' => $target
        ];
    }

    /**
     * 获取目标用户
     */
    private function getTargetUsers(string $target): Collection
    {
        return match ($target) {
            'all' => TelegramUser::all(),
            'recent' => $this->userService->getRecentlyActiveUsers(7),
            'recent_30' => $this->userService->getRecentlyActiveUsers(30),
            'inactive' => TelegramUser::where('is_active', false)->get(),
            default => $this->userService->getActiveUsers()
        };
    }

    /**
     * 按语言分组获取目标用户
     */
    private function getTargetUsersByLanguage(string $target): Collection
    {
        $users = $this->getTargetUsers($target);
        return $users->groupBy('language');
    }

    /**
     * 获取群发统计信息
     */
    public function getBroadcastStats(): array
    {
        $userStats = $this->userService->getUserStats();
        
        return [
            'available_targets' => [
                'all' => $userStats['total_users'],
                'active' => $userStats['active_users'],
                'inactive' => $userStats['inactive_users'],
                'recent_7_days' => $this->userService->getRecentlyActiveUsers(7)->count(),
                'recent_30_days' => $this->userService->getRecentlyActiveUsers(30)->count(),
            ],
            'language_distribution' => $userStats['language_distribution'],
            'users_with_language_selected' => $userStats['users_with_language_selected']
        ];
    }
}