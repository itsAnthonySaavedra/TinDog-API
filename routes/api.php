<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// Protected routes (Require Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // User Management
    Route::get('/users', [UserController::class, 'index']);       // List
    Route::get('/users/{id}', [UserController::class, 'show']);   // View
    Route::put('/users/{id}', [UserController::class, 'update']); // Edit
    Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete
});