<?php

namespace Naph\Searchlight\Tests;

use Illuminate\Console\Application;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;
use Naph\Searchlight\SearchlightServiceProvider;

class SearchlightTestCase extends TestCase
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * Set up TestCase
     */
    protected function setUp()
    {
        parent::setUp();

        $this->refreshDatabase();
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

        $app->register(SearchlightServiceProvider::class);

        return $app;
    }

    /**
     * Run migrations
     */
    public function refreshDatabase()
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
