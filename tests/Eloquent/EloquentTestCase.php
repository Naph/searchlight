<?php

namespace Naph\Searchlight\Tests\Eloquent;

use Naph\Searchlight\Drivers\Eloquent\EloquentDriver;
use Naph\Searchlight\Search;
use Naph\Searchlight\Tests\SearchlightTestCase;

class EloquentTestCase extends SearchlightTestCase
{
    /**
     * @var EloquentDriver
     */
    protected $driver;

    /**
     * @var Search
     */
    protected $search;

    protected function setUp()
    {
        parent::setUp();

        $this->driver = $this->app['searchlight']->driver('eloquent');
        $this->search = $this->app['searchlight.search']->driver('eloquent');
    }
}
