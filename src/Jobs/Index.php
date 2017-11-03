<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Model\SearchlightContract;

class Index implements ShouldQueue
{
    use Queueable;

    /**
     * @var SearchlightContract
     */
    protected $model;

    /**
     * Index constructor.
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
        $driver->index($this->model);
    }
}
