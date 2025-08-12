<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\TelegramUser;

use App\Services\TelegramWebhookService;
use App\Services\TelegramMessageService;
use App\Services\TelegramBroadcastService;
use App\Services\TelegramLanguageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TelegramBotController extends Controller
{
    public function __construct(
        private TelegramWebhookService $webhookService,
        private TelegramMessageService $messageService,
        private TelegramBroadcastService $broadcastService
    ) {}

    /**
     * 处理Telegram webhook
     */
    public function webhook(Request $request)
    {
        try {
            $update = Telegram::commandsHandler(true);

            if ($update) {
                $this->webhookService->handleUpdate($update);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            return response('Error', 500);
        }
    }

    /**
     * 管理员发送消息给用户
     */
    public function sendMessageToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:telegram_users,id',
            'message' => 'required|string|max:4096'
        ]);

        $success = $this->messageService->sendMessageToUser(
            $request->user_id,
            $request->message
        );

        if ($request->header('X-Inertia')) {
            // Inertia 请求：返回重定向 + 闪存
            return back()->with(
                $success
                    ? ['success' => '消息已加入发送队列']
                    : ['error' => '发送失败，请检查用户ID']
            );
        }

        // 非 Inertia：返回 JSON
        if ($success) {
            return response()->json(['success' => true, 'message' => '消息已加入发送队列']);
        }
        return response()->json(['success' => false, 'message' => '发送失败，请检查用户ID'], 400);
    }

    /**
     * 群发消息
     */
    public function broadcastMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'target' => 'sometimes|string|in:all,active,recent,recent_30'
        ]);

        $target = $request->get('target', 'active');

        $result = $this->broadcastService->broadcast(
            $request->message,
            $target
        );

        if ($request->header('X-Inertia')) {
            // Inertia 请求：返回重定向 + 闪存
            return back()->with([
                'success' => "消息将发送给 {$result['sent']} 个用户",
                'broadcastResult' => $result,
            ]);
        }

        // 非 Inertia：返回 JSON
        return response()->json([
            'success' => true,
            'message' => "消息将发送给 {$result['sent']} 个用户",
            'details' => $result
        ]);
    }



    /**
     * 获取群发统计信息
     */
    public function getBroadcastStats()
    {
        $stats = $this->broadcastService->getBroadcastStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 获取Telegram用户列表
     */
    public function getUsers(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');

        $query = TelegramUser::with('user')->orderBy('last_interaction', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('telegram_user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * 切换用户状态
     */
    public function toggleUserStatus(TelegramUser $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', '用户状态已更新');
    }

    /**
     * 显示用户管理页面
     */
    public function usersPage(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');

        $query = TelegramUser::with('user')->orderBy('last_interaction', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('telegram_user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return inertia('Telegram/Users', [
            'users' => $users
        ]);
    }

    /**
     * 显示消息广播页面
     */
    public function broadcastPage()
    {
        $stats = $this->broadcastService->getBroadcastStats();

        return inertia('Telegram/Broadcast', [
            'stats' => $stats
        ]);
    }
}
