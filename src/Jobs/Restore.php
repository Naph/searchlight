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
    protected $model;

    /**
     * Restore constructor.
     * @param SearchlightContract $model
     */
    public function __construct(SearchlightContract $model)
    {
        $this->model = $model;
    }

    /**
     * @param Driver $driver
     */
    public function handle(Driver $driver)
    {
        $driver->restore($this->model);
    }
}
