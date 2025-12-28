<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', LogoutController::class)
    ->middleware('auth:sanctum')
    ->name('logout');

Route::post('/forgot-password', ForgotPasswordController::class)->name('password.email');
Route::post('/reset-password', ResetPasswordController::class)->name('password.update');
