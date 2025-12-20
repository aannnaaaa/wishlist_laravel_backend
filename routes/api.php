<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\WishlistItemController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // Wishlists
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlists', [WishlistController::class, 'store']);
    Route::get('/wishlists/{wishlist}', [WishlistController::class, 'show']);
    Route::put('/wishlists/{wishlist}', [WishlistController::class, 'update']);
    Route::delete('/wishlists/{wishlist}', [WishlistController::class, 'destroy']);

    // Wishlist Items
    Route::get('/wishlists/{wishlist}/items', [WishlistItemController::class, 'index']);
    Route::post('/wishlists/{wishlist}/items', [WishlistItemController::class, 'store']);
    Route::get('/items/{item}', [WishlistItemController::class, 'show']);
    Route::put('/items/{item}', [WishlistItemController::class, 'update']);
    Route::delete('/items/{item}', [WishlistItemController::class, 'destroy']);

    // Reservations
    Route::post('/items/{item}/reserve', [WishlistItemController::class, 'reserve']);
    Route::post('/items/{item}/unreserve', [WishlistItemController::class, 'unreserve']);
});
