<?php

namespace Naph\Searchlight\Tests;

use Illuminate\Console\Application;
use Illuminate\Contracts\Console\Kernel;
use Naph\Searchlight\SearchlightServiceProvider;
use PHPUnit\Framework\TestCase;

class SearchlightTestCase extends TestCase
{
    /**
     * @var Application
     */
    protected $app;

    protected function setUp()
    {
        $this->app = $this->createApplication();
        $this->setUpDatabase();
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(SearchlightServiceProvider::class);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function setUpDatabase()
    {
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
    }
}
