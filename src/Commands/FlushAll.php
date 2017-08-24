<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Bus\Dispatcher;
use Illuminate\Console\Command;
use Naph\Searchlight\Jobs\DeleteAll;

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
     * Handle the synchronous command
     *
     * @param Dispatcher $dispatcher
     */
    public function handle(Dispatcher $dispatcher)
    {
        $dispatcher->dispatch(
            new DeleteAll([])
        );
        $this->info('All Searchlight indices have been flushed.');
    }
}
