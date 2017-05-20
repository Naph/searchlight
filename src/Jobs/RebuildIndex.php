<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Naph\Searchlight\SearchlightDriver;

class RebuildIndex implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $classes;

    /**
     * RebuildIndex constructor.
     *
     * @param string[] $classes
     */
    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public function handle(SearchlightDriver $driver, Dispatcher $dispatcher)
    {
        $driver->deleteAll();

        foreach ($this->classes as $class) {
            $dispatcher->dispatch(new BuildIndex($class));
        }
    }
}
