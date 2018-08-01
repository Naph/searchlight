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

    /**
     * The array of created drivers
     *
     * @var array
     */
    protected $drivers = [];

    public function register()
    {
        $runningInConsole = $this->app->runningInConsole();
        $bindMethod = $runningInConsole ? 'bind' : 'singleton';

        $this->mergeConfigFrom(
            __DIR__.'/../config/searchlight.php', 'searchlight'
        );

        // Singleton the Searchlight driver manager
        $this->app->{$bindMethod}('searchlight', function ($app) {
            return new DriverManager($app);
        });

        // Singleton the default Searchlight driver
        $this->app->{$bindMethod}(Driver::class, function ($app) {
            return $app['searchlight']->driver();
        });

        // Singleton the Searchlight search builder
        $this->app->{$bindMethod}(Search::class, function ($app) {
            return new Search($app);
        });

        // Register commands when running in console
        if ($runningInConsole) {
            $this->commands([
                Commands\Flush::class,
                Commands\FlushAll::class,
                Commands\Index::class,
                Commands\IndexAll::class,
                Commands\Rebuild::class
            ]);

            $this->publishes([
                __DIR__.'/../config/searchlight.php' => config_path('/searchlight.php'),
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
        return [
            Search::class,
            Driver::class,
        ];
    }
}
