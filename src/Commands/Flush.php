<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Bus\Dispatcher;
use Illuminate\Console\Command;
use Naph\Searchlight\Jobs\DeleteAll;

class Flush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:flush {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush a searchlight index';

    /**
     * Handle the synchronous command
     *
     * @param Dispatcher $dispatcher
     */
    public function handle(Dispatcher $dispatcher)
    {
        $model = $this->argument('model');
        $dispatcher->dispatch(
            new DeleteAll([$this->laravel->make($model)])
        );
        $this->info("Searchlight index for $model has been flushed.");
    }
}
