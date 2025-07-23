<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-register-otp', [AuthController::class, 'resendRegisterOtp']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API works!']);
});

// Route::middleware('auth:sanctum')->get('/profile', [AuthController::class, 'profile']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-forgot-otp', [AuthController::class, 'verifyForgotOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
