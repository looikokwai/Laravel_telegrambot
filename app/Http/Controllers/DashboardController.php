<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 显示仪表板页面
     */
    public function index()
    {
        // 获取统计数据
        $stats = [
            'available_targets' => [
                'all' => TelegramUser::count(),
                'active' => TelegramUser::where('is_active', true)->count(),
                'recent_7_days' => TelegramUser::where('last_interaction', '>=', now()->subDays(7))->count(),
            ],
            'users_with_language_selected' => TelegramUser::whereNotNull('language')->count(),
        ];

        // 获取最近用户
        $recentUsers = TelegramUser::orderBy('last_interaction', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Telegram/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
        ]);
    }
}