<?php

namespace Naph\Searchlight\Tests;

use Illuminate\Console\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;
use Naph\Searchlight\SearchlightServiceProvider;

class SearchlightTestCase extends TestCase
{
    /**
     * Set up TestCase
     */
    protected function setUp()
    {
        parent::setUp();

        $this->runMigrations();
    }

    /**
     * Overloaded createApplication
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']['searchlight.repositories'] = [TestModel::class];

        $app->register(SearchlightServiceProvider::class);

        return $app;
    }

    /**
     * Run migrations
     */
    public function runMigrations()
    {
        // Models
        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('location');
            $table->timestamps();
        });
    }
}
