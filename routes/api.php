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

Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::post('/add/{product_id}',  [CartController::class, 'add']);
    Route::get('/',                   [CartController::class, 'getCartProducts']);
    Route::delete('/clear',           [CartController::class, 'deleteAll']);
    Route::delete('/remove',         [CartController::class, 'deleteProducts']);
    Route::patch('/update/{product_id}',     [CartController::class, 'update']);
});

////////   inventory   //////////////////
Route::middleware('auth:sanctum')->prefix('inventory')->group(function () {
    Route::get('/',                [InventoryController::class, 'index']);
    Route::get('/{productId}',     [InventoryController::class, 'show']);
    Route::put('/{productId}',     [InventoryController::class, 'update']);
});

////////   orders   //////////////////
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    Route::get('/',                [OrderController::class, 'index']);
    Route::post('/checkout',               [OrderController::class, 'checkout']); // Create order + Process payment
    Route::get('/{order}',         [OrderController::class, 'show']);
    Route::put('/{id}/status',     [OrderController::class, 'updateStatus'])->middleware('role:Admin');
});

////////   wallet   //////////////////
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/topup', [WalletController::class, 'topUp']);
});

Route::get('/node-info', function () {
    return response()->json([
        'hostname'    => gethostname(),
        'server_id'   => env('SERVER_ID', 'unknown'),
        'server_ip'   => request()->server('SERVER_ADDR'),
        'timestamp'   => now()->toISOString(),
        'php_version' => PHP_VERSION,
    ]);
});

// ← أضف هذا في نهاية الملف
Route::get('/lb/test/products', function () {
    $products = \App\Models\Product::with('category')->limit(10)->get();
    return response()->json([
        'node'   => gethostname(),
        'action' => 'GET products',
        'count'  => $products->count(),
        'data'   => $products,
    ]);
});

Route::get('/lb/test/categories', function () {
    $categories = \App\Models\Category::all();
    return response()->json([
        'node'   => gethostname(),
        'action' => 'GET categories',
        'data'   => $categories,
    ]);
});

Route::get('/lb/test/inventory', function () {
    $inventory = \App\Models\Inventory::with('product')->get();
    return response()->json([
        'node'   => gethostname(),
        'action' => 'GET inventory',
        'data'   => $inventory,
    ]);
});

Route::get('/lb/test/orders', function () {
    $orders = \App\Models\Order::limit(10)->get();
    usleep(300000); // 300ms
    return response()->json([
        'node'   => gethostname(),
        'action' => 'GET orders',
        'count'  => $orders->count(),
        'data'   => $orders,
    ]);
});

// هذا فقط يبقى داخل auth
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/lb/test/cart/{product_id}', function ($product_id) {
        $product = \App\Models\Product::findOrFail($product_id);
        usleep(300000); // 300ms
        return response()->json([
            'node'    => gethostname(),
            'action'  => 'ADD to cart',
            'product' => $product->name,
        ]);
    });
});
