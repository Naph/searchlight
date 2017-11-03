<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Model\SearchlightContract;

class BuildIndex implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * @var SearchlightContract
     */
    protected $repository;

    /**
     * BuildIndex constructor.
     * @param SearchlightContract $repository
     */
    public function __construct(SearchlightContract $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Driver $driver
     * @param Dispatcher $dispatcher
     */
    public function handle(Driver $driver, Dispatcher $dispatcher)
    {
        set_time_limit(0);

        $model = $this->repository;

        if (method_exists($model, 'withTrashed')) {
            $model = $model->withTrashed();
        }

        // Loop through and push each model to the index
        $model->chunk(1000, function ($models) use ($driver, $dispatcher) {
            foreach ($models as $model) {
                $dispatcher->dispatch(new Index($model));
            }
        });
    }
}
