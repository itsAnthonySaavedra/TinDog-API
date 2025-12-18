<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\SwipeController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/admin-login', [AuthController::class, 'adminLogin']);
Route::post('/user-login', [AuthController::class, 'userLogin']);
Route::post('/register', [AuthController::class, 'register']);

// Debug route (temporary - for performance testing)
Route::get('/debug/performance', [App\Http\Controllers\DebugController::class, 'performance']);
Route::get('/debug/messages/{userId}/{otherUserId}', [App\Http\Controllers\DebugController::class, 'testMessages']);

// Protected routes (Require Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // User Management
    Route::get('/user/me', [UserController::class, 'me']); // Lightweight User Info
    Route::get('/users', [UserController::class, 'index']);       // List
    Route::post('/users', [UserController::class, 'store']);      // Create
    Route::get('/users/{id}', [UserController::class, 'show']);   // View
    Route::put('/users/{id}', [UserController::class, 'update']); // Edit
    Route::put('/users/{id}/password', [UserController::class, 'changePassword']); // Change Password
    Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete
    
    // Admin Features
    Route::get('/admin/matches', [App\Http\Controllers\AdminMatchController::class, 'index']);

    // Block/Unblock
    Route::post('/users/{id}/block', [UserController::class, 'block']);
    Route::delete('/users/{id}/unblock', [UserController::class, 'unblock']);
    Route::get('/users/blocked', [UserController::class, 'blocked']);

    // Subscriptions & Invoices
    Route::post('/subscription/subscribe', [App\Http\Controllers\SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel']);
    Route::get('/user/invoices', function (Illuminate\Http\Request $request) {
        return $request->user()->invoices;
    });
    Route::get('/user/invoices/{id}', function (Illuminate\Http\Request $request, $id) {
        return $request->user()->invoices()->findOrFail($id);
    });

    // Reports
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index']);
    Route::post('/reports', [App\Http\Controllers\ReportController::class, 'store']); // Create Report
    Route::put('/reports/{id}', [App\Http\Controllers\ReportController::class, 'update']);

    // Analytics
    Route::get('/analytics/overview', [App\Http\Controllers\AnalyticsController::class, 'overview']);
    Route::get('/analytics/recent-activity', [App\Http\Controllers\AnalyticsController::class, 'recentActivity']);
    Route::get('/analytics/user-growth', [App\Http\Controllers\AnalyticsController::class, 'userGrowth']);
    Route::get('/analytics/demographics', [App\Http\Controllers\AnalyticsController::class, 'demographics']);
    Route::get('/analytics/engagement', [App\Http\Controllers\AnalyticsController::class, 'engagement']);
    Route::get('/analytics/revenue', [App\Http\Controllers\AnalyticsController::class, 'revenue']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);

    // System Settings
    Route::get('/settings', [App\Http\Controllers\SystemSettingController::class, 'index']);
    Route::put('/settings', [App\Http\Controllers\SystemSettingController::class, 'update']);
    Route::get('/user/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index']);
    
    // Phase 2: Discovery & Matching
    Route::get('/discovery', [DiscoveryController::class, 'index']);
    Route::post('/swipe', [SwipeController::class, 'store']);
    Route::get('/matches', [MatchController::class, 'index']);

    // Phase 3: Messages
    Route::get('/messages/{userId}', [App\Http\Controllers\MessageController::class, 'index']);
    Route::post('/messages', [App\Http\Controllers\MessageController::class, 'store']);
});