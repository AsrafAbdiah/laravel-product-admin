<?php

namespace App\Providers;

use App\Models\Product;
use App\Support\CurrentTeam;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One CurrentTeam per request (or queued job), shared by every class
        // that asks for it. "scoped" rather than "singleton" so it is reset
        // between requests on long-running servers like Octane.
        $this->app->scoped(CurrentTeam::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pagination links in the BandarOS style (themed, dark-mode aware).
        Paginator::defaultView('pagination.default');

        // The nav shows how many products are in the trash. A view composer
        // keeps that query out of the Blade template itself.
        View::composer('components.layout', function ($view) {
            $view->with('trashCount', auth()->check() ? Product::onlyTrashed()->count() : 0);
        });
    }
}
