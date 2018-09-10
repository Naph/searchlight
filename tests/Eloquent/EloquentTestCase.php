<?php

namespace Naph\Searchlight\Tests\Eloquent;

use Naph\Searchlight\Drivers\Eloquent\Driver;
use Naph\Searchlight\Search;
use Naph\Searchlight\SearchlightServiceProvider;
use Naph\Searchlight\Tests\SearchlightTestCase;

class EloquentTestCase extends SearchlightTestCase
{
    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var Search
     */
    protected $search;

    protected function setUp()
    {
        parent::setUp();

        $this->app['config']['searchlight.driver'] = 'eloquent';
        $this->app->register(SearchlightServiceProvider::class);
        $this->search = $this->app['searchlight.search'];
        $this->driver = $this->app['searchlight.driver'];
    }
}
