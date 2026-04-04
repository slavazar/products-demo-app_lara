<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\User\Product\CategoryController as ProductCategoryController;
use App\Http\Controllers\Api\User\ProductController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/auth/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Protected routes for authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Product routes
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product/categories', ProductCategoryController::class);

    Route::get('product/category/list', [ProductCategoryController::class, 'list'])
        ->name('api.product.categories.list');

    // Additional product image routes
    Route::delete('products/{product}/images/{image}', [ProductController::class, 'deleteImage']);
    Route::patch('products/{product}/images/order', [ProductController::class, 'updateImageOrder']);
    Route::patch('products/{product}/images/primary', [ProductController::class, 'setPrimaryImage']);
});
