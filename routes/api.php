<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/email/verify/{id}/{hash}', function () {
    return response()->json(['message' => 'Email verificado!']);
})->name('verification.verify');

Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
