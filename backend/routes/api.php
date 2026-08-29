<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BestSellerController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

// Public Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Content routes
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/best-sellers', [BestSellerController::class, 'index']);

Route::prefix('books')->group(function () {
    Route::get('/search', [BookController::class, 'search']);
    Route::get('/latest', [BookController::class, 'latest']);
    Route::get('/{id}', [BookController::class, 'show'])->whereNumber('id');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/{bookId}', [FavoriteController::class, 'store'])->whereNumber('bookId');
        Route::delete('/{bookId}', [FavoriteController::class, 'destroy'])->whereNumber('bookId');
    });
});
