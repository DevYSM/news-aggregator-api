<?php

namespace App\Providers;

use App\Services\News\AggregatorService;
use App\Services\News\Sources\GuardianService;
use App\Services\News\Sources\NewsApiService;
use App\Services\News\Sources\NytService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the AggregatorService with its dependencies as constructor arguments to make it available globally
        $this->app->bind(AggregatorService::class, fn () => new AggregatorService(
            sources: [
                new NewsApiService,
                new GuardianService,
                new NytService,
            ]
        ));
    }

    public function boot(): void
    {
        //
    }
}
