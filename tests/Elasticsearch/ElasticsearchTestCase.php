<?php

namespace Naph\Searchlight\Tests\Elasticsearch;

use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver;
use Naph\Searchlight\Tests\SearchlightTestCase;
use Naph\Searchlight\Tests\TestModel;

class ElasticsearchTestCase extends SearchlightTestCase
{
    /**
     * @var ElasticsearchDriver
     */
    protected $driver;

    protected function setUp()
    {
        parent::setUp();

        $config = require __DIR__ . '/../../config/searchlight.php';
        $this->driver = new ElasticsearchDriver([TestModel::class], $config['drivers']['elasticsearch']);
    }
}
