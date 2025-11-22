<?php

namespace App\Providers;

use App\Contracts\GenukaServiceInterface;
use App\Services\Genuka\GenukaService;
use Illuminate\Support\ServiceProvider;

class GenukaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('genuka', function ($app) {
            return new GenukaService;
        });

        $this->app->bind(GenukaServiceInterface::class, function ($app) {
            return $app->make('genuka');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
