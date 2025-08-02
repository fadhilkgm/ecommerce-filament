<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API v1 routes
Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Test endpoint for debugging
    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is working!',
            'timestamp' => now()->toISOString(),
        ]);
    });

    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Address management routes
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        Route::post('/addresses/{id}/set-default', [AddressController::class, 'setDefault']);
        
        // Checkout routes
        Route::get('/checkout', [CheckoutController::class, 'index']);
        Route::post('/checkout/process', [CheckoutController::class, 'processCheckout']);
        
        // Order management routes
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    });

    // Cart routes - support both authenticated and guest users
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
});
