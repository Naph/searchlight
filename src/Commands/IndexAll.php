<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Console\Command;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Jobs\BuildIndex;

class IndexAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:index-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all searchlight indexes';

    /**
     * Handle the command
     *
     * @param Dispatcher $dispatcher
     * @param Driver $driver
     * @return void
     */
    public function handle(Dispatcher $dispatcher, Driver $driver)
    {
        foreach ($driver->getRepositories() as $repository) {
            $dispatcher->dispatch(
                new BuildIndex($this->laravel->make($repository))
            );
        }

        $this->info('All searchlight indexes have been imported.');
    }
}
