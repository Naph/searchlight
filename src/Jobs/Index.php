<?php
declare(strict_types=1);

namespace Naph\Searchlight\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Naph\Searchlight\SearchlightDriver;
use Naph\Searchlight\SearchlightContract;

class Index implements ShouldQueue
{
    use Queueable;

    protected $searchable;

    public function __construct(SearchlightContract $searchable)
    {
        $this->searchable = $searchable;
    }

    public function handle(SearchlightDriver $driver)
    {
        $driver->index($this->searchable);
    }
}
