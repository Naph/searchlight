<?php

namespace Naph\Searchlight\Drivers\Eloquent;

use Naph\Searchlight\Model\Decorator;
use Naph\Searchlight\{
    Builder, Driver
};

class EloquentDriver extends Driver
{
    public $supportsIndexing = false;

    /**
     * Return new instance of plugin builder
     *
     * @return Builder
     */
    public function builder(): Builder
    {
        return new EloquentBuilder($this);
    }

    /**
     * Update search indices
     *
     * @param  Decorator[] $models
     *
     * @return void
     */
    protected function index(...$models): void
    {
        // No support
    }

    /**
     * Delete models indices
     *
     * @param Decorator[] $models
     *
     * @return void
     */
    protected function delete(...$models): void
    {
        // No support
    }

    /**
     * Restore deleted search indices
     *
     * @param Decorator[] $models
     *
     * @return void
     */
    protected function restore(...$models): void
    {
        // No support
    }

    /**
     * Flush indices of model type
     *
     * @param Decorator[] $models
     *
     * @return void
     */
    protected function flush(...$models): void
    {
        // No support
    }
}
