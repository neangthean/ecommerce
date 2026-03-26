<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutoSliderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\SizeGroupController;
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

// Product Routes But Random
Route::get('/show-all-products-pagination', [ProductController::class, 'indexPagination']);

// Category Routes
Route::post('/store-category', [CategoryController::class, 'store']);
Route::get('/show-all-categories', [CategoryController::class, 'index']);
// http://127.0.0.1:8000/api/show-category/1 (1 is id of categories)
Route::get('/show-category/{category}', [CategoryController::class, 'show']);
Route::post('/update-category/{category}', [CategoryController::class, 'update']);

// Auto Slider Routes
Route::post('/store-auto-slider', [AutoSliderController::class, 'store']);
Route::get('/show-all-auto-slider', [AutoSliderController::class, 'index']);

// Cart Routes
Route::post('/add-to-cart', [CartController::class, 'addToCart'])->middleware('auth:api');
Route::get('/show-cart', [CartController::class, 'showCart'])->middleware('auth:api');
Route::put('/update-cart-quantity', [CartController::class, 'updateCartQuantity'])->middleware('auth:api');
// http://127.0.0.1:8000/api/delete-from-cart/1 (1 is id of carts)
Route::delete('/delete-from-cart/{id}', [CartController::class, 'deleteFromCart'])->middleware('auth:api');

// Reviews Routes
Route::post('/store-update-review', [ReviewController::class, 'store'])->middleware('auth:api');
// http://127.0.0.1:8000/api/show-review/1 (1 is id of products)
Route::get('/show-review/{id}', [ReviewController::class, 'showByProduct']);
// http://127.0.0.1:8000/api/delete-review/1 (1 is id of reviews)
Route::delete('/delete-review/{id}', [ReviewController::class, 'destroy'])->middleware('auth:api');

// Size Group Route
Route::post('/size-group/create', [SizeGroupController::class, 'create']);
Route::get('/size-group/show', [SizeGroupController::class, 'show']);

// Size Route
Route::post('/size/create', [SizeController::class, 'create']);
Route::get('/size/show', [SizeController::class, 'show']);
Route::get('/size/show/size-group-id', [SizeController::class, 'showBySizeGroupID']);

// Checkout Routes
Route::post('/checkout/process-checkout', [CheckoutController::class, 'processCheckout'])->middleware('auth:api');
Route::post('/checkout/ship_order', [CheckoutController::class, 'shipOrder'])->middleware('auth:api');

// Order history / detail (after successful checkout)
Route::get('/orders', [CheckoutController::class, 'listOrders'])->middleware('auth:api');
Route::get('/orders/{order}', [CheckoutController::class, 'getOrderDetail'])->middleware('auth:api');
