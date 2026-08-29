<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\OpenLibraryService::class, function () {
            return new \App\Services\OpenLibraryService();
        });

        $this->app->singleton(\App\Services\RssNewsService::class, function () {
            return new \App\Services\RssNewsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
