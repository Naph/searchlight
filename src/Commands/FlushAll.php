<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Console\Command;
use Naph\Searchlight\Driver;

class FlushAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:flush-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flushes all searchlight indexes';

    /**
     * Handle the command
     *
     * @param Driver $driver
     * @return void
     */
    public function handle(Driver $driver)
    {
        $driver->deleteAll();
        $this->info('All searchlight indexes have been flushed.');
    }
}
