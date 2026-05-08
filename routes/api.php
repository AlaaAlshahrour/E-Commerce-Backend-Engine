<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

////////////   Auth   /////////////////////

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

////////   Product & Category   //////////////////


Route::middleware('throttle:public-api')->group(function () {
    Route::apiResource('products', ProductController::class)
        ->except(['store', 'update', 'destroy']);

    Route::apiResource('categories', CategoryController::class)
        ->except(['store', 'update', 'destroy']);
});


Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('products', ProductController::class)
        ->except(['index', 'show']);

    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);
});

////////   cart   //////////////////

Route::middleware(['auth:sanctum', 'throttle:cart'])->prefix('cart')->group(function () {
    Route::post('/add/{product_id}',  [CartController::class, 'add']);
    Route::get('/',                   [CartController::class, 'getCartProducts']);
    Route::delete('/clear',           [CartController::class, 'deleteAll']);
    Route::delete('/remove',         [CartController::class, 'deleteProducts']);
    Route::patch('/update/{product_id}',     [CartController::class, 'update']);
});

////////   inventory   //////////////////
Route::middleware(['auth:sanctum', 'throttle:inventory-update'])->prefix('inventory')->group(function () {
    Route::get('/',                [InventoryController::class, 'index']);
    Route::get('/{productId}',     [InventoryController::class, 'show']);
    Route::put('/{productId}',     [InventoryController::class, 'update']);
});

////////   orders   //////////////////
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/',                [OrderController::class, 'index'])->middleware('throttle:authenticated-api');
    Route::post('/checkout',               [OrderController::class, 'checkout'])->middleware('throttle:checkout');
    Route::get('/{order}',         [OrderController::class, 'show'])->middleware('throttle:authenticated-api');
    Route::put('/{id}/status',     [OrderController::class, 'updateStatus'])->middleware([
            'role:Admin',
            'throttle:admin-actions'
        ]);
});

////////   wallet   //////////////////
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show'])->middleware('throttle:authenticated-api');
    Route::post('/wallet/topup', [WalletController::class, 'topUp'])->middleware('throttle:wallet');
});
