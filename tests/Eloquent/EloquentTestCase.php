<?php

namespace Naph\Searchlight\Tests\Eloquent;

use Naph\Searchlight\Drivers\Eloquent\EloquentDriver;
use Naph\Searchlight\Tests\SearchlightTestCase;
use Naph\Searchlight\Tests\TestModel;

class EloquentTestCase extends SearchlightTestCase
{
    /**
     * @var EloquentDriver
     */
    protected $driver;

    protected function setUp()
    {
        parent::setUp();

        $this->driver = $this->app['searchlight']->driver('eloquent');
    }
}
