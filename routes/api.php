<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserInteractionController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
Route::get('/feed', [FeedController::class, 'feed'])->name('feed');
Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

Route::middleware(['web'])->group(function () {
    Route::post('/auth/register', [UserController::class, 'register'])->name('register');
    Route::post('/auth/login', [UserController::class, 'login'])->name('login');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/password-request', [UserController::class, 'passwordRequest'])->name('password.request');
    Route::post('/auth/password-reset', [UserController::class, 'passwordReset'])->name('password.reset');

    Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirect'])->name('auth.redirect');
    Route::get('/auth/{provider}/callback', [AuthController::class, 'callback'])->name('auth.callback');

    Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/markdown', [\App\Http\Controllers\MarkdownController::class, 'preview'])->name('markdown.preview');

    Route::get('/user', [UserController::class, 'show'])->name('user.show');
    Route::put('/user', [UserController::class, 'update'])->name('user.update');

    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [PostController::class, 'delete'])->name('posts.delete');
    Route::post('/posts/{id}/restore', [PostController::class, 'restore'])->name('posts.restore');

    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{id}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{id}', [CommentController::class, 'delete'])->name('comments.delete');
    Route::post('/comments/{id}/restore', [CommentController::class, 'restore'])->name('comments.restore');

    Route::post('/interactions', [UserInteractionController::class, 'store'])->name('interactions.store');
    Route::get('/interactions/posts', [UserInteractionController::class, 'posts'])->name('interactions.posts');
});
