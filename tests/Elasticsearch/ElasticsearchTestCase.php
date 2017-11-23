<?php

namespace Naph\Searchlight\Tests\Elasticsearch;

use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver;
use Naph\Searchlight\Search;
use Naph\Searchlight\Tests\SearchlightTestCase;

class ElasticsearchTestCase extends SearchlightTestCase
{
    /**
     * @var ElasticsearchDriver
     */
    protected $driver;

    /**
     * @var Search
     */
    protected $search;

    protected function setUp()
    {
        parent::setUp();

        $this->driver = $this->app['searchlight']->driver('elasticsearch');
        $this->search = $this->app['searchlight.search']->driver('elasticsearch');
    }
}
