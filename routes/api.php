<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/posts/{id}', [\App\Http\Controllers\PostController::class, 'show'])->name('posts.show');

Route::middleware(['auth:sanctum'])->group(function ($routes) {
    Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('user.show');
    Route::put('/user', [\App\Http\Controllers\UserController::class, 'update'])->name('user.update');

    Route::post('/posts', [\App\Http\Controllers\PostController::class, 'store'])->name('posts.store');
    Route::put('/posts/{id}', [\App\Http\Controllers\PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [\App\Http\Controllers\PostController::class, 'delete'])->name('posts.delete');
    Route::post('/posts/{id}/restore', [\App\Http\Controllers\PostController::class, 'restore'])->name('posts.restore');
});
