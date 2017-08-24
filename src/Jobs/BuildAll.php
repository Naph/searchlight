<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Naph\Searchlight\Model\SearchlightContract;

class BuildAll implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    protected $models;

    /**
     * @param SearchlightContract[] $models
     */
    public function __construct(array $models)
    {
        $this->models = $models;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function handle(Dispatcher $dispatcher)
    {
        foreach ($this->models as $model) {
            $dispatcher->dispatch(new Build($model));
        }
    }
}
