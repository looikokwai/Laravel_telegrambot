<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TelegramMenuController;
use App\Http\Controllers\TelegramImageController;
use App\Http\Controllers\TelegramLanguageController;
use App\Http\Controllers\TelegramGroupController;
use App\Http\Controllers\TelegramChannelController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 测试路由 (开发环境)
Route::get('/log-test', function () {
    Log::info('Log test: This is a test info message.');
    throw new \Exception('This is a test exception to check logging.');
});

// 根路径重定向
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
})->name('home');

/*
|--------------------------------------------------------------------------
| 认证相关路由
|--------------------------------------------------------------------------
*/

// 访客路由 (未登录用户)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 已认证用户路由
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| Telegram Bot 路由
|--------------------------------------------------------------------------
*/

// Webhook 路由 (无需认证)
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| 需要认证的应用路由
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |----------------------------------------------------------------------
    | 仪表板和主要页面路由
    |----------------------------------------------------------------------
    */

    // 仪表板
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Telegram 重定向
    Route::get('/telegram', function () {
        return redirect()->route('dashboard');
    })->name('telegram.dashboard');

    /*
    |----------------------------------------------------------------------
    | Telegram 页面路由
    |----------------------------------------------------------------------
    */

    // 用户管理页面
    Route::get('/telegram/users', [TelegramBotController::class, 'usersPage'])->name('telegram.users.page');

    // 消息广播页面
    Route::get('/telegram/broadcast', [TelegramBotController::class, 'broadcastPage'])->name('telegram.broadcast.page');

    // 菜单管理页面
    Route::get('/telegram/menu-management', [TelegramMenuController::class, 'index'])->name('telegram.menu.page');

    // 图片管理页面
    Route::get('/telegram/image-management', [TelegramImageController::class, 'index'])->name('telegram.image.page');

    // 语言管理页面
    Route::get('/telegram/language-management', [TelegramLanguageController::class, 'index'])->name('telegram.language.page');

    // 群组管理页面
    Route::get('/telegram/group-management', [TelegramGroupController::class, 'index'])->name('telegram.group.page');

    // 群组广播页面
    Route::get('/telegram/group-broadcast', [TelegramGroupController::class, 'broadcastPage'])->name('telegram.group.broadcast.page');

    // 频道管理页面
    Route::get('/telegram/channel-management', [TelegramChannelController::class, 'index'])->name('telegram.channel.page');

    // 频道广播页面
    Route::get('/telegram/channel-broadcast', [TelegramChannelController::class, 'broadcastPage'])->name('telegram.channel.broadcast.page');

    /*
    |----------------------------------------------------------------------
    | Telegram API 路由
    |----------------------------------------------------------------------
    */

    // 消息管理 API
    Route::post('/telegram/send-message', [TelegramBotController::class, 'sendMessageToUser'])->name('telegram.send-message');
    Route::post('/telegram/broadcast', [TelegramBotController::class, 'broadcastMessage'])->name('telegram.broadcast');
    Route::get('/telegram/broadcast-stats', [TelegramBotController::class, 'getBroadcastStats'])->name('telegram.broadcast-stats');

    // 群组广播 API
    Route::post('/telegram/group-broadcast', [TelegramGroupController::class, 'broadcastMessage'])->name('telegram.group.broadcast');
    Route::get('/telegram/group-broadcast-stats', [TelegramGroupController::class, 'getBroadcastStats'])->name('telegram.group.broadcast-stats');

    // 频道广播 API
    Route::post('/telegram/channel-broadcast', [TelegramChannelController::class, 'broadcastMessage'])->name('telegram.channel.broadcast');
    Route::get('/telegram/channel-broadcast-stats', [TelegramChannelController::class, 'getBroadcastStats'])->name('telegram.channel.broadcast-stats');

    // 用户管理 API
    Route::get('/telegram/users/data', [TelegramBotController::class, 'getUsers'])->name('telegram.users.data');
    Route::post('/telegram/users/{user}/toggle-status', [TelegramBotController::class, 'toggleUserStatus'])->name('telegram.users.toggle-status');

    /*
    |----------------------------------------------------------------------
    | Telegram 资源管理路由
    |----------------------------------------------------------------------
    */

    // 菜单管理
    Route::prefix('telegram/menu')->name('telegram.menu.')->group(function () {
        // 基础 CRUD
        Route::get('/', [TelegramMenuController::class, 'index'])->name('index');
        Route::post('/', [TelegramMenuController::class, 'store'])->name('store');
        Route::get('/{menuItem}', [TelegramMenuController::class, 'show'])->name('show');
        Route::put('/{menuItem}', [TelegramMenuController::class, 'update'])->name('update');
        Route::patch('/{menuItem}', [TelegramMenuController::class, 'update'])->name('patch');
        Route::delete('/{menuItem}', [TelegramMenuController::class, 'destroy'])->name('destroy');

        // 菜单操作
        Route::post('/reorder', [TelegramMenuController::class, 'updateOrder'])->name('reorder');
        Route::patch('/{menuItem}/toggle-status', [TelegramMenuController::class, 'toggleStatus'])->name('toggle-status');

        // 缓存和数据管理
        Route::post('/cache/clear', [TelegramMenuController::class, 'clearCache'])->name('clear-cache');
        Route::post('/export', [TelegramMenuController::class, 'exportMenu'])->name('export');
        Route::post('/import', [TelegramMenuController::class, 'importMenu'])->name('import');
    });

    // 图片管理
    Route::prefix('telegram/images')->name('telegram.images.')->group(function () {
        // 基础 CRUD
        Route::get('/', [TelegramImageController::class, 'index'])->name('index');
        Route::get('/api', [TelegramImageController::class, 'index'])->name('api'); // API端点
        Route::post('/upload', [TelegramImageController::class, 'store'])->name('upload');

        // 筛选功能
        Route::get('/filter', [TelegramImageController::class, 'filter'])->name('filter.get');
        Route::post('/filter', [TelegramImageController::class, 'filter'])->name('filter');

        // 批量操作
        Route::post('/batch-delete', [TelegramImageController::class, 'bulkDestroy'])->name('batch-destroy');

        // 菜单关联
        Route::post('/attach-to-menu', [TelegramImageController::class, 'attachToMenuItem'])->name('attach-to-menu');
        Route::post('/detach-from-menu', [TelegramImageController::class, 'detachFromMenuItem'])->name('detach-from-menu');

        // 图片处理
        Route::post('/{image}/optimize', [TelegramImageController::class, 'optimize'])->name('optimize');
        Route::get('/serve/{filename}', [TelegramImageController::class, 'serveImage'])->name('serve');

        // 参数路由放在最后
        Route::get('/{image}', [TelegramImageController::class, 'show'])->name('show');
        Route::put('/{image}', [TelegramImageController::class, 'update'])->name('update');
        Route::delete('/{image}', [TelegramImageController::class, 'destroy'])->name('destroy');
    });

    // 语言管理
    Route::prefix('telegram/languages')->name('telegram.languages.')->group(function () {
        // 基础 CRUD
        Route::get('/', [TelegramLanguageController::class, 'index'])->name('index');
        Route::post('/', [TelegramLanguageController::class, 'store'])->name('store');

        // 筛选功能
        Route::get('/filter', [TelegramLanguageController::class, 'filter'])->name('filter.get');
        Route::post('/filter', [TelegramLanguageController::class, 'filter'])->name('filter');

        // 语言操作
        Route::post('/reorder', [TelegramLanguageController::class, 'updateOrder'])->name('reorder');

        // 翻译管理
        Route::post('/translations/batch-create', [TelegramLanguageController::class, 'batchCreateTranslations'])->name('translations.batch-create');

        // 数据管理
        Route::post('/export', [TelegramLanguageController::class, 'exportLanguageData'])->name('export');
        Route::post('/import', [TelegramLanguageController::class, 'importLanguageData'])->name('import');
        Route::post('/cache/clear', [TelegramLanguageController::class, 'clearCache'])->name('clear-cache');

        // 参数路由放在最后
        Route::get('/{language}', [TelegramLanguageController::class, 'show'])->name('show');
        Route::put('/{language}', [TelegramLanguageController::class, 'update'])->name('update');
        Route::delete('/{language}', [TelegramLanguageController::class, 'destroy'])->name('destroy');
        Route::post('/{language}/set-default', [TelegramLanguageController::class, 'setDefault'])->name('set-default');
        Route::post('/{language}/toggle-status', [TelegramLanguageController::class, 'toggleStatus'])->name('toggle-status');
    });

    // 群组管理
    Route::prefix('telegram/groups')->name('telegram.groups.')->group(function () {
        // 基础 CRUD
        Route::get('/', [TelegramGroupController::class, 'index'])->name('index');
        Route::post('/', [TelegramGroupController::class, 'store'])->name('store');
        Route::put('/{group}', [TelegramGroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [TelegramGroupController::class, 'destroy'])->name('destroy');

        // 群组操作
        Route::post('/{group}/send-message', [TelegramGroupController::class, 'sendMessage'])->name('send-message');
        Route::get('/{group}/stats', [TelegramGroupController::class, 'getStats'])->name('stats');
        Route::put('/{group}/toggle-status', [TelegramGroupController::class, 'toggleStatus'])->name('toggle-status');

        // 权限验证
        Route::post('/validate-permission', [TelegramGroupController::class, 'validatePermission'])->name('validate-permission');

        // API 端点
        Route::get('/api/list', [TelegramGroupController::class, 'getGroups'])->name('api.list');
    });

    // 频道管理
    Route::prefix('telegram/channels')->name('telegram.channels.')->group(function () {
        // 基础 CRUD
        Route::get('/', [TelegramChannelController::class, 'index'])->name('index');
        Route::post('/', [TelegramChannelController::class, 'store'])->name('store');
        Route::put('/{channel}', [TelegramChannelController::class, 'update'])->name('update');
        Route::delete('/{channel}', [TelegramChannelController::class, 'destroy'])->name('destroy');

        // 频道操作
        Route::post('/{channel}/publish-message', [TelegramChannelController::class, 'publishMessage'])->name('publish-message');
        Route::get('/{channel}/stats', [TelegramChannelController::class, 'getStats'])->name('stats');
        Route::put('/{channel}/toggle-status', [TelegramChannelController::class, 'toggleStatus'])->name('toggle-status');

        // 权限验证
        Route::post('/validate-permission', [TelegramChannelController::class, 'validatePermission'])->name('validate-permission');

        // API 端点
        Route::get('/api/list', [TelegramChannelController::class, 'getChannels'])->name('api.list');
    });
});
