<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Console\Command;

class Rebuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:rebuild';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flushes and imports all searchlight indexes';

    /**
     * Handle the command
     *
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        $this->call('searchlight:flush-all');
        $this->call('searchlight:import-all');
    }
}
