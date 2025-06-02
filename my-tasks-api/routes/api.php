<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\Auth\AuthController; 

// Public auth routes
Route::prefix('auth')->group(function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);
    });
});

// Protected API routes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});


