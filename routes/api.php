<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\food\FoodController;
use App\Http\Controllers\order\OrderController;
use App\Http\Controllers\table\TableController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/tables', [TableController::class, 'index']);

    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);

        // Food routes - accessible by pelayan and kasir only
        Route::middleware('role:pelayan,kasir')->group(function () {
            Route::get('/foods', [FoodController::class, 'index']);
            Route::post('/foods', [FoodController::class, 'store']);
            Route::match(['put', 'post'], '/foods/{id}', [FoodController::class, 'update']);
            Route::delete('/foods/{id}', [FoodController::class, 'destroy']);
        });

        // Order routes - accessible by pelayan and kasir
        Route::middleware('role:pelayan,kasir')->group(function () {
            Route::get('/orders', [OrderController::class, 'index']);
            Route::get('/orders/{id}', [OrderController::class, 'show']);
            Route::get('/orders/{id}/receipt', [OrderController::class, 'generateReceipt']);
            Route::post('/orders/{id}/close', [OrderController::class, 'closeOrder']);
        });

        // Order routes - accessible by pelayan only
        Route::middleware('role:pelayan')->group(function () {
            Route::post('/orders/open', [OrderController::class, 'open']);
            Route::post('/orders/{order_id}/items', [OrderController::class, 'addItem']);
        });
    });
});
