<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Naph\Searchlight\Driver;
use Naph\Searchlight\Model\SearchlightContract;

class DeleteAll
{
    protected $models;

    /**
     * @param SearchlightContract[] $models
     */
    public function __construct(array $models)
    {
        $this->models = $models;
    }

    /**
     * @param Driver $driver
     */
    public function handle(Driver $driver)
    {
        $driver->deleteAll($this->models);
    }
}
