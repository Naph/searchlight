<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Naph\Searchlight\Driver;

class BuildIndex implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    protected $repository;

    public function __construct(string $repository)
    {
        $this->repository = App::make($repository);
    }

    public function handle(Driver $driver, Dispatcher $dispatcher)
    {
        set_time_limit(0);

        // Loop through and push each model to the index
        $this->repository->withTrashed()->chunk(1000, function ($models) use ($driver, $dispatcher) {
            foreach ($models as $model) {
                $dispatcher->dispatch(new Index($model));
            }
        });
    }
}
