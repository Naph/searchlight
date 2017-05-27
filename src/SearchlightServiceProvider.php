<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Support\ServiceProvider;
use Naph\Searchlight\Commands\IndexAll;

class SearchlightServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IndexAll::class,
            ]);
        }
    }

    public function register()
    {
        $this->publishes([
            __DIR__.'/../config/searchlight.php' => config_path('searchlight.php')
        ], 'searchlight');

        $this->mergeConfigFrom(
            __DIR__.'/../config/searchlight.php', 'searchlight'
        );

        $this->app->singleton(Driver::class, function($app) {
            $driver = $app['config']->get('searchlight.driver');
            return new $driver['class']($app['config']->get('searchlight.index'), $driver['config']);
        });
    }

    public function provides()
    {
        return [
            Driver::class
        ];
    }
}
