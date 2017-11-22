<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\ServiceProvider;
use Naph\Searchlight\Commands;
use Naph\Searchlight\Jobs;
use Naph\Searchlight\Model\SearchlightContract;

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
        $this->mergeConfigFrom(
            __DIR__.'/../config/searchlight.php', 'searchlight'
        );

        // Singleton the Searchlight DriverManager
        $this->app->singleton('searchlight', function ($app) {
            return new DriverManager($app);
        });

        // Singleton the default Searchlight Driver
        $this->app->singleton('searchlight.driver', function ($app) {
            return $app['searchlight']->driver();
        });

        // Instance concrete driver to interface
        $this->app->instance(Driver::class, $this->app['searchlight.driver']);

        // Bind events when supporting indexing
        if ($this->app[Driver::class]->supportsIndexing) {
            $bus = $this->app->make(BusDispatcher::class);
            $events = $this->app->make(EventsDispatcher::class);

            $events->listen(['eloquent.saved: *'], function ($model) use ($bus) {
                if ($model instanceof SearchlightContract) {
                    $bus->dispatch(new Jobs\Index($model));
                }
            });

            $events->listen(['eloquent.deleted: *'], function ($model) use ($bus) {
                if ($model instanceof SearchlightContract) {
                    $bus->dispatch(new Jobs\Delete($model));
                }
            });

            $events->listen(['eloquent.restored: *'], function ($model) use ($bus) {
                if ($model instanceof SearchlightContract) {
                    $bus->dispatch(new Jobs\Restore($model));
                }
            });
        }

        // Register commands when running in console
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
