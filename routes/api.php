<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PlanFeatureController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\InvoiceController;

Route::get('/user', function (Request $request) {
    return $request->user()->load(['subscriptions' => function ($query) {
        $query->where('status', 'active');
    }, 'subscriptions.plan']);
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

Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::get('plans/{plan:slug}', [PlanController::class, 'show'])->name('plans.show');

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
    Route::get('/features', [PlanFeatureController::class, 'index'])->name('features.index');
    Route::post('/plans/{plan}/features', [PlanFeatureController::class, 'store'])->name('plans.features.store');
    Route::delete('/plans/{plan}/features/{feature}', [PlanFeatureController::class, 'destroy'])->name('plans.features.destroy');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('/subscriptions/swap', [SubscriptionController::class, 'swap'])->name('subscriptions.swap');
    Route::delete('/subscriptions', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
});

Route::get('/invoices', [InvoiceController::class, 'index'])
    ->middleware('auth:sanctum')
    ->name('invoices.index');

Route::post('/webhooks/payment', [WebhookController::class, 'handle'])->name('webhooks.payment');