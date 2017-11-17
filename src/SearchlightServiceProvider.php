<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\ServiceProvider;
use Naph\Searchlight\Commands;
use Naph\Searchlight\Jobs\Delete;
use Naph\Searchlight\Jobs\Index;
use Naph\Searchlight\Jobs\Restore;
use Naph\Searchlight\Model\SearchlightContract;

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
        /*$this->mergeConfigFrom(
            __DIR__.'/../config/searchlight.php', 'searchlight'
        );*/

        // Singleton the searchlight driver
        $this->app->singleton(Driver::class, function($app) {
            $config = $app['config']->get('searchlight');
            $driver = $config['drivers'][$config['driver']];
            $repositories = $config['repositories'];

            return new $driver['class']($repositories, $driver);
        });

        // Listen to events
        $bus = $this->app->make(BusDispatcher::class);
        $events = $this->app->make(EventsDispatcher::class);

        $events->listen(['eloquent.saved: *'], function ($model) use ($bus) {
            if ($model instanceof SearchlightContract) {
                $bus->dispatch(new Index($model));
            }
        });

        $events->listen(['eloquent.deleted: *'], function ($model) use ($bus) {
            if ($model instanceof SearchlightContract) {
                $bus->dispatch(new Delete($model));
            }
        });

        $events->listen(['eloquent.restored: *'], function ($model) use ($bus) {
            if ($model instanceof SearchlightContract) {
                $bus->dispatch(new Restore($model));
            }
        });

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
