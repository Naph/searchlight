<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Naph\Searchlight\Driver;
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
     * @param Driver $driver
     * @return void
     */
    public function handle(Driver $driver)
    {
        $model = $this->laravel->make($this->argument('model'));
        $driver->handleFlush($model);
        $this->info("Searchlight index for {$this->argument('model')} has been flushed.");
    }
}
