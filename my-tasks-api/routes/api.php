<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\Auth\AuthController; 

Route::group([
    'middleware' => 'api', 
    'prefix' => 'auth'     
], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});


Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});


