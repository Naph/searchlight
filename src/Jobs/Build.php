<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Naph\Searchlight\Model\SearchlightContract;

class Build implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    protected $model;

    /**
     * @param SearchlightContract $model
     */
    public function __construct(SearchlightContract $model)
    {
        $this->model = $model;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function handle(Dispatcher $dispatcher)
    {
        $model = $this->model;

        if (method_exists($model, 'withTrashed')) {
            $model = $model->withTrashed();
        }

        $model->chunk(1000, function ($models) use ($dispatcher) {
            foreach ($models as $model) {
                $dispatcher->dispatch(new Index($model));
            }
        });
    }
}
