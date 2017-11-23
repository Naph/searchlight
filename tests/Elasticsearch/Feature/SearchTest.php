<?php

namespace Naph\Searchlight\Tests\Elasticsearch\Feature;

use Naph\Searchlight\Tests\Elasticsearch\ElasticsearchTestCase;
use Naph\Searchlight\Tests\TestModel;

class SearchTest extends ElasticsearchTestCase
{
    /**
     * @test
     */
    public function match()
    {
        $this->driver::setMockedResponse([
            'hits' => [
                'hits' => [
                    [
                        '_source' => [
                            'id' => 1
                        ]
                    ]
                ]
            ]
        ]);

        TestModel::create([
            'name' => 'My Name!',
            'email' => 'email@example.com',
            'location' => 'A Street!',
        ]);

        $collection = $this->search->in(new TestModel())
            ->match('My Name!')
            ->get();

        $this->assertTrue($collection->isNotEmpty());
    }
}
