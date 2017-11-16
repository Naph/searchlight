<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Model\SearchlightContract;

class Restore implements ShouldQueue
{
    use Queueable;

    /**
     * @var SearchlightContract
     */
    protected $models;

    /**
     * Index constructor.
     * @param SearchlightContract[] $models
     */
    public function __construct(SearchlightContract ...$models)
    {
        $this->models = $models;
    }


    /**
     * @param Driver $driver
     */
    public function handle(Driver $driver)
    {
        $driver->handleRestore($this->models);
    }
}
