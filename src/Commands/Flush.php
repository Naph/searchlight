<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\App;
use Naph\Searchlight\Jobs\Delete;

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
     * Handle the command
     *
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function handle(Dispatcher $dispatcher)
    {
        $model = $this->argument('model');
        $dispatcher->dispatch(
            new Delete(App::make($model))
        );
        $this->info("Searchlight index for $model has been flushed.");
    }
}
