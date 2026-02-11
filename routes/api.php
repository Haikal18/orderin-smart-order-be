<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\food\FoodController;
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
    });
});
