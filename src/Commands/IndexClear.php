<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Console\Command;
use Naph\Searchlight\SearchlightDriver;

class IndexClear extends Command
{
    protected $signature = 'index:clear';

    protected $driver;

    public function __construct(SearchlightDriver $driver)
    {
        parent::__construct();

        $this->driver = $driver;
    }

    public function handle()
    {
        $this->info('Clearing all indices...');
        $this->driver->deleteAll();
    }
}
