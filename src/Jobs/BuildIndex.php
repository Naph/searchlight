<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Naph\Searchlight\SearchlightDriver;

class BuildIndex implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    protected $class;

    public function __construct(string $class)
    {
        $this->class = App::make($class);
    }

    public function handle(SearchlightDriver $driver, Dispatcher $dispatcher)
    {
        set_time_limit(0);

        // Loop through and push each model to the index
        $this->class->chunk(1000, function ($models) use ($driver, $dispatcher) {
            foreach ($models as $model) {
                $dispatcher->dispatch(new Index($model));
            }
        });
    }
}
