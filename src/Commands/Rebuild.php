<?php
declare(strict_types=1);

namespace Naph\Searchlight\Commands;

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
     * @return void
     */
    public function handle()
    {
        $this->call('searchlight:flush-all');
        $this->call('searchlight:index-all');
    }
}
