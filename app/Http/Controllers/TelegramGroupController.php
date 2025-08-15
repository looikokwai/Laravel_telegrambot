<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\TelegramGroupService;
use App\Services\TelegramGroupBroadcastService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TelegramGroupController extends Controller
{
    public function __construct(
        private TelegramGroupService $groupService,
        private TelegramGroupBroadcastService $broadcastService
    ) {}

    /**
     * 群组管理页面
     */
    public function index(): Response
    {
        $groups = $this->groupService->getGroups();

        return Inertia::render('Telegram/GroupManagement', [
            'groups' => $groups
        ]);
    }

    /**
     * 群组广播页面
     */
    public function broadcastPage(): Response
    {
        $groups = $this->groupService->getGroups(['active' => true]);
        $stats = $this->broadcastService->getBroadcastStats();
        $broadcasts = $this->broadcastService->getBroadcastHistory();

        return Inertia::render('Telegram/GroupBroadcast', [
            'groups' => $groups,
            'stats' => $stats,
            'broadcasts' => $broadcasts
        ]);
    }

    /**
     * 添加群组
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_id' => 'required|string|max:255',
        ]);

        $result = $this->groupService->addGroup($request->group_id);

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
     * 编辑群组
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $success = $this->groupService->updateGroup($id, $request->only(['title', 'description', 'is_active']));

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '群组更新成功']
                    : ['error' => '群组更新失败']
            );
        }

        return response()->json(['success' => $success]);
    }

    /**
     * 删除群组
     */
    public function destroy(Request $request, int $id)
    {
        $success = $this->groupService->deleteGroup($id);

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '群组删除成功']
                    : ['error' => '群组删除失败']
            );
        }

        return response()->json(['success' => $success]);
    }

    /**
     * 发送消息到群组
     */
    public function sendMessage(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
            'keyboard' => 'nullable|array'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Storage::putFileAs('telegram/group-images', $image, $image->getClientOriginalName());
        }

        $result = $this->groupService->sendMessage(
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
                    ? ['success' => '消息发送成功']
                    : ['error' => '消息发送失败: ' . $result['error']]
            );
        }

        return response()->json($result);
    }

    /**
     * 群组广播消息
     */
    public function broadcastMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:4096',
            'target_groups' => 'required|array',
            'target_type' => 'required|string|in:all,selected,active',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120',
            'keyboard' => 'nullable|array'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = Storage::putFileAs('telegram/group-broadcast-images', $image, $image->getClientOriginalName());
        }

        $result = $this->broadcastService->broadcast(
            $request->message,
            $request->target_groups,
            $request->target_type,
            [],
            $imagePath,
            $request->keyboard
        );

        if ($request->header('X-Inertia')) {
            return back()->with('success', "群组广播已加入队列，目标群组: {$result['total_groups']} 个");
        }

        return response()->json($result);
    }

    /**
     * 获取群组统计
     */
    public function getStats(int $id)
    {
        $stats = $this->groupService->getGroupStats($id);

        if (request()->header('X-Inertia')) {
            return back()->with('stats', $stats);
        }

        return response()->json($stats);
    }

    /**
     * 获取群组广播统计
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
     * 验证群组权限
     */
    public function validatePermission(Request $request)
    {
        $request->validate([
            'group_id' => 'required|string'
        ]);

        $result = $this->groupService->validateGroupPermission($request->group_id);

        return response()->json($result);
    }

    /**
     * 获取群组列表 (API)
     */
    public function getGroups(Request $request)
    {
        $filters = $request->only(['active', 'type', 'search', 'sort_by', 'sort_dir']);
        $groups = $this->groupService->getGroups($filters);

        return response()->json($groups);
    }

    /**
     * 切换群组状态
     */
    public function toggleStatus(Request $request, int $id)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $success = $this->groupService->updateGroup($id, ['is_active' => $request->is_active]);

        if ($request->header('X-Inertia')) {
            return back()->with(
                $success
                    ? ['success' => '群组状态更新成功']
                    : ['error' => '群组状态更新失败']
            );
        }

        return response()->json(['success' => $success]);
    }
}
