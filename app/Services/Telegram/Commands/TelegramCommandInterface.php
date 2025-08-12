<?php

namespace App\Services\Telegram\Commands;

use App\Models\TelegramUser;

/**
 * Telegram命令处理器接口
 */
interface TelegramCommandInterface
{
    /**
     * 执行命令
     *
     * @param TelegramUser $user
     * @param array $message
     * @return array
     */
    public function execute(TelegramUser $user, array $message): array;

    /**
     * 获取命令名称
     *
     * @return string
     */
    public function getCommandName(): string;

    /**
     * 获取命令描述
     *
     * @return string
     */
    public function getDescription(): string;
}