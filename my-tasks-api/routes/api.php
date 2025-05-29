<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TaskController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;


Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['guest:' . config('fortify.guard')]);

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest:' . config('fortify.guard')]);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['auth:' . config('fortify.guard')]);

if (config('fortify.features.reset-passwords')) {
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['guest:' . config('fortify.guard')])
        ->name('password.email'); 

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware(['guest:' . config('fortify.guard')])
        ->name('password.update'); 
}


if (Features::enabled(Features::emailVerification())) {
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth' . config('fortify.guard'), 'throttle:6,1']);

    Route::post('/email/verify/{id}/{hash}', [VerifyEmailController::class, '_invoke'])
        ->middleware('auth'. config('fortify.guard'), 'signed', 'throttle:6,1');
}

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('tasks', TaskController::class);
});
