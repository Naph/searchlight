<?php

namespace Naph\Searchlight\Tests\Elasticsearch;

use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver;
use Naph\Searchlight\Search;
use Naph\Searchlight\SearchlightServiceProvider;
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

        $this->app['config']['searchlight.driver'] = 'elasticsearch';
        $this->app->register(SearchlightServiceProvider::class);
        $this->search = $this->app['searchlight.search'];
        $this->driver = $this->app['searchlight.driver'];
    }
}
