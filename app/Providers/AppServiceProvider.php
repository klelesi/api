<?php

namespace App\Providers;

use App\CustomCursorPaginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(\Illuminate\Pagination\CursorPaginator::class, CustomCursorPaginator::class);
    }
}
