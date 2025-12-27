<?php

use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\WishlistItemController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //пользователь
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [UserController::class, 'update']);

    //вишлисты
    Route::get('/wishlists', [WishlistController::class, 'index']);
    Route::post('/wishlists', [WishlistController::class, 'store']);
    Route::get('/wishlists/{wishlist}', [WishlistController::class, 'show']);
    Route::put('/wishlists/{wishlist}', [WishlistController::class, 'update']);
    Route::delete('/wishlists/{wishlist}', [WishlistController::class, 'destroy']);

    //подарочки
    Route::get('/wishlists/{wishlist}/items', [WishlistItemController::class, 'index']);
    Route::post('/wishlists/{wishlist}/items', [WishlistItemController::class, 'store']);
    Route::get('/items/{item}', [WishlistItemController::class, 'show']);
    Route::put('/items/{item}', [WishlistItemController::class, 'update']);

    Route::delete('/items/{item}', [WishlistItemController::class, 'destroy']);

    //резервация
    Route::post('/items/{item}/reserve', [WishlistItemController::class, 'reserve']);
    Route::post('/items/{item}/unreserve', [WishlistItemController::class, 'unreserve']);

    // Друзья
    Route::get('/friends', [FriendshipController::class, 'index']);                    // список друзей
    Route::post('/friends', [FriendshipController::class, 'store']);                   // отправить запрос
    Route::get('/friends/pending', [FriendshipController::class, 'pending']);         // входящие запросы
    Route::put('/friends/{friendship}', [FriendshipController::class, 'update']);      // принять/отклонить
    Route::delete('/friends/{friendship}', [FriendshipController::class, 'destroy']);  // удалить друга

    Route::get('/friends/{user}/profile', [UserController::class, 'friendProfile']);

    // поиск пользователей
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    // пользователи (для поиска друзей)
    Route::get('/users', [UserController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::put('/notifications/{notification}', [NotificationController::class, 'update']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    // бронь
    Route::get('/reservations', [ReservationController::class, 'index']);
});
