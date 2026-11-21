<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\Facades\View;
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
        // The nav shows how many products are in the trash. A view composer
        // keeps that query out of the Blade template itself.
        View::composer('components.layout', function ($view) {
            $view->with('trashCount', auth()->check() ? Product::onlyTrashed()->count() : 0);
        });
    }
}
