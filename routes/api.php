<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoSliderController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// http://127.0.0.1:8000/api

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
// http://127.0.0.1:8000/api/show-all-products
// http://127.0.0.1:8000/api/show-all-products?category_id=2
Route::get('/show-all-products', [ProductController::class, 'index']); // if we want to filter we put query parameter. Example usage in URL: /api/products?category_id=1
// http://127.0.0.1:8000/api/show-product/1 (1 is id of products)
Route::get('/show-product/{product}', [ProductController::class, 'show']); // {product} is must be id of products

// Category Routes
Route::post('/store-category', [CategoryController::class, 'store']);
Route::get('/show-all-categories', [CategoryController::class, 'index']);
// http://127.0.0.1:8000/api/show-category/1 (1 is id of categories)
Route::get('/show-category/{category}', [CategoryController::class, 'show']);

// Auto Slider Routes
Route::post('/store-auto-slider', [AutoSliderController::class, 'store']);
Route::get('/show-all-auto-slider', [AutoSliderController::class, 'index']);
