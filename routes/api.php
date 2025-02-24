<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/posts/{id}', [\App\Http\Controllers\PostController::class, 'show'])->name('posts.show');
Route::get('/feed', [\App\Http\Controllers\FeedController::class, 'feed'])->name('feed');

Route::middleware(['web'])->group(function () {
    Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('auth.callback');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('user.show');
    Route::put('/user', [\App\Http\Controllers\UserController::class, 'update'])->name('user.update');

    Route::post('/posts', [\App\Http\Controllers\PostController::class, 'store'])->name('posts.store');
    Route::put('/posts/{id}', [\App\Http\Controllers\PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [\App\Http\Controllers\PostController::class, 'delete'])->name('posts.delete');
    Route::post('/posts/{id}/restore', [\App\Http\Controllers\PostController::class, 'restore'])->name('posts.restore');

    Route::post('/comments', [\App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{id}', [\App\Http\Controllers\CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{id}', [\App\Http\Controllers\CommentController::class, 'delete'])->name('comments.delete');
    Route::post('/comments/{id}/restore', [\App\Http\Controllers\CommentController::class, 'restore'])->name('comments.restore');
});
