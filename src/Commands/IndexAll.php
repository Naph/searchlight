<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Bus\Dispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Naph\Searchlight\Jobs\RebuildIndex;
use Naph\Searchlight\SearchlightDriver;

class IndexAll extends Command
{
    protected $signature = 'index:all';

    protected $driver;

    protected $repositories;

    public function __construct(SearchlightDriver $driver)
    {
        parent::__construct();

        $this->driver = $driver;
        $this->repositories = Config::get('searchlight.repositories');
    }

    public function handle(Dispatcher $dispatcher)
    {
        $dispatcher->dispatch(new RebuildIndex($this->repositories));
    }
}
