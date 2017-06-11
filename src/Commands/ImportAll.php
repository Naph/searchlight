<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Naph\Searchlight\Jobs\BuildIndex;

class ImportAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:import-all';

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
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        $repositories = Config::get('searchlight.repositories');
        foreach ($repositories as $repository) {
            $dispatcher->dispatch(
                new BuildIndex($repository)
            );
        }
        $this->info('All searchlight indexes have been imported.');
    }
}
