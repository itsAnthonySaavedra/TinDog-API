<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\SwipeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/admin-login', [AuthController::class, 'adminLogin']);
Route::post('/user-login', [AuthController::class, 'userLogin']);
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| WebSocket / Broadcasting Auth
|--------------------------------------------------------------------------
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    Route::get('/user/me', [UserController::class, 'me']);
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index']);
    Route::post('/reports', [App\Http\Controllers\ReportController::class, 'store']);
    Route::put('/reports/{id}', [App\Http\Controllers\ReportController::class, 'update']);

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
    Route::get('/analytics/overview', [App\Http\Controllers\AnalyticsController::class, 'overview']);
    Route::get('/analytics/recent-activity', [App\Http\Controllers\AnalyticsController::class, 'recentActivity']);
    Route::get('/analytics/user-growth', [App\Http\Controllers\AnalyticsController::class, 'userGrowth']);
    Route::get('/analytics/demographics', [App\Http\Controllers\AnalyticsController::class, 'demographics']);
    Route::get('/analytics/engagement', [App\Http\Controllers\AnalyticsController::class, 'engagement']);
    Route::get('/analytics/revenue', [App\Http\Controllers\AnalyticsController::class, 'revenue']);

    /*
    |--------------------------------------------------------------------------
    | System Settings
    |--------------------------------------------------------------------------
    */
    Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index']);
    Route::put('/settings', [App\Http\Controllers\SystemSettingController::class, 'update']);

    Route::get('/user/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Discovery & Matching
    |--------------------------------------------------------------------------
    */
    Route::get('/discovery', [DiscoveryController::class, 'index']);
    Route::post('/swipe', [SwipeController::class, 'store']);
    Route::get('/matches', [MatchController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Messaging System (Real-Time Chat)
    |--------------------------------------------------------------------------
    */

    // 🟢 Chat List (recent chats, unread badge)
    Route::get('/chats', [ChatController::class, 'getUserChats']);

    // 🟢 Start or get a conversation
    Route::post('/chats/start', [ChatController::class, 'startConversation']);

    // 🟢 Fetch messages inside conversation
    Route::get('/chats/{id}/messages', [MessageController::class, 'getMessages']);

    // 🟢 Send a text message
    Route::post('/chats/{id}/send', [MessageController::class, 'sendMessage']);

    // 🟢 Send an image
    Route::post('/chats/{id}/send-image', [MessageController::class, 'sendImage']);

    // 🟢 Mark messages as seen
    Route::post('/chats/{id}/seen', [MessageController::class, 'markAsSeen']);

    // 🟢 Typing indicator
    Route::post('/chats/{id}/typing', [MessageController::class, 'typing']);
});
