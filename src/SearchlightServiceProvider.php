<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Support\ServiceProvider;
use Naph\Searchlight\Commands;

class SearchlightServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/searchlight.php', 'searchlight'
        );

        // Singleton the searchlight driver
        $this->app->singleton(Driver::class, function($app) {
            $config = $app['config']->get('searchlight');
            $driver = $config['drivers'][$config['driver']];
            return new $driver['class']($driver);
        });

        // Register console commands in running in consoles
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\Flush::class,
                Commands\FlushAll::class,
                Commands\Index::class,
                Commands\IndexAll::class,
                Commands\Rebuild::class
            ]);

            $this->publishes([
                __DIR__.'/../config/searchlight.php' => $this->app->make('path.config').'/searchlight.php'
            ], 'searchlight');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [Driver::class];
    }
}
