<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

////////////   Auth   /////////////////////

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

////////   Product & Category   //////////////////

Route::apiResource('products', ProductController::class)
    ->except(['store', 'update', 'destroy']);

Route::apiResource('categories', CategoryController::class)
    ->except(['store', 'update', 'destroy']);

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('products', ProductController::class)
        ->except(['index', 'show']);

    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);
});

////////   cart   //////////////////

Route::middleware('auth:sanctum')->group(function () {
    Route::post('carts/{store}/products/{product}/add', [CartController::class, 'add']);
    Route::get('carts/products', [CartController::class, 'getCartProducts']);
    Route::delete('carts/delete-all', [CartController::class, 'deleteAll']);
});
