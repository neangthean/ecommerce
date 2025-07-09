<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('login', [AuthController::class, 'login']);
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api'); // Require bearer token
Route::post('/update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:api'); // Require bearer token
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api'); // Require bearer token


// Products Routes
Route::post('/store-product', [ProductController::class, 'store']);
Route::post('/store-category', [CategoryController::class, 'store']);
Route::get('/show-all-products', [ProductController::class, 'index']);
Route::get('/show-product/{product}', [ProductController::class, 'show']);