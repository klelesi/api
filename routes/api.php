<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function ($routes) {
    Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('user.show');
    Route::put('/user', [\App\Http\Controllers\UserController::class, 'update'])->name('user.update');
});
