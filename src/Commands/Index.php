<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Console\Command;
use Naph\Searchlight\Jobs\BuildIndex;

class Index extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchlight:index {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a searchlight index';

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
            new BuildIndex($model)
        );
        $this->info("Searchlight index for $model has been imported.");
    }
}
