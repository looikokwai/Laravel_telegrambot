<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\TelegramChannelService;
use App\Services\TelegramChannelBroadcastService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TelegramChannelController extends Controller
{
    public function __construct(
        private TelegramChannelService $channelService,
        private TelegramChannelBroadcastService $broadcastService
    ) {}

    /**
     * 频道管理页面
     */
    public function index(): Response
    {
        $channels = $this->channelService->getChannels();

        return Inertia::render('Telegram/ChannelManagement', [
            'channels' => $channels
        ]);
    }

    /**
     * 频道广播页面
     */
    public function broadcastPage(): Response
    {
        $channels = $this->channelService->getChannels(['active' => true]);
        $stats = $this->broadcastService->getBroadcastStats();
        $broadcasts = $this->broadcastService->getBroadcastHistory();

        return Inertia::render('Telegram/ChannelBroadcast', [
            'channels' => $channels,
            'stats' => $stats,
            'broadcasts' => $broadcasts
        ]);
    }

    /**
     * 添加频道
     */
    public function store(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|string|max:255',
        ]);

        $result = $this->channelService->addChannel($request->channel_id);

        if ($request->header('X-Inertia')) {
            return back()->with(
                $result['success']
                    ? ['success' => $result['message']]
                    : ['error' => $result['error']]
            );
        }

        return response()->json($result);
    }

    /**
     * 编辑频道
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $success = $this->channelService->updateChannel($id, $request->only(['title', 'description', 'is_active']));

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '频道更新成功']
                    : ['error' => '频道更新失败']
            );
        }

        return response()->json(['success' => $success]);
    }

    /**
     * 删除频道
     */
    public function destroy(Request $request, int $id)
    {
        $success = $this->channelService->deleteChannel($id);

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '频道删除成功']
                    : ['error' => '频道删除失败']
            );
        }

        return response()->json(['success' => $success]);
    }

    /**
     * 发布消息到频道
     */
    public function publishMessage(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
            'keyboard' => 'nullable|array'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Storage::putFileAs('telegram/channel-images', $image, $image->getClientOriginalName());
        }

        $result = $this->channelService->publishMessage(
            $id,
            $request->message,
            [
                'image_path' => $imagePath,
                'keyboard' => $request->keyboard
            ]
        );

        if ($request->header('X-Inertia')) {
            return back()->with(
                $result['success']
                    ? ['success' => '消息发布成功']
                    : ['error' => '消息发布失败: ' . $result['error']]
            );
        }

        return response()->json($result);
    }

    /**
     * 频道广播消息
     */
    public function broadcastMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'target_channels' => 'required|array',
            'target_type' => 'required|string|in:all,selected,active',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
            'keyboard' => 'nullable|array'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Storage::putFileAs('telegram/channel-broadcast-images', $image, $image->getClientOriginalName());
        }

        $result = $this->broadcastService->broadcast(
            $request->message,
            $request->target_channels,
            $request->target_type,
            [],
            $imagePath,
            $request->keyboard
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', "频道广播已加入队列，目标频道: {$result['total_channels']} 个");
        }

        return response()->json($result);
    }

    /**
     * 获取频道统计
     */
    public function getStats(int $id)
    {
        $stats = $this->channelService->getChannelStats($id);

        if (request()->header('X-Inertia')) {
            return back()->with('stats', $stats);
        }

        return response()->json($stats);
    }

    /**
     * 获取频道广播统计
     */
    public function getBroadcastStats()
    {
        $stats = $this->broadcastService->getBroadcastStats();

        if (request()->header('X-Inertia')) {
            return back()->with('broadcastStats', $stats);
        }

        return response()->json($stats);
    }

    /**
     * 验证频道权限
     */
    public function validatePermission(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|string'
        ]);

        $result = $this->channelService->validateChannelPermission($request->channel_id);

        return response()->json($result);
    }

    /**
     * 获取频道列表 (API)
     */
    public function getChannels(Request $request)
    {
        $filters = $request->only(['active', 'public', 'search', 'sort_by', 'sort_dir']);
        $channels = $this->channelService->getChannels($filters);

        return response()->json($channels);
    }

    /**
     * 切换频道状态
     */
    public function toggleStatus(Request $request, int $id)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $success = $this->channelService->updateChannel($id, ['is_active' => $request->is_active]);

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '频道状态更新成功']
                    : ['error' => '频道状态更新失败']
            );
        }

        return response()->json(['success' => $success]);
    }
}
