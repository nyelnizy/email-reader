<?php

namespace Hwa\EmailReader;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EmailReaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/email-reader.php', 'email-reader');

        $this->publishConfig();

         $this->loadViewsFrom(__DIR__.'/resources/views', 'email-reader');
         $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        });
    }

    /**
    * Get route group configuration array.
    *
    * @return array
    */
    private function routeConfiguration()
    {
        return [
            'namespace'  => "Hwa\EmailReader\Http\Controllers",
            'middleware' => 'api',
            'prefix'     => 'api'
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register facade
        $this->app->singleton('email-reader', function () {
            return new EmailReader;
        });
    }

    /**
     * Publish Config
     *
     * @return void
     */
    public function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/email-reader.php' => config_path('email-reader.php'),
            ], 'config');
        }
    }
}
