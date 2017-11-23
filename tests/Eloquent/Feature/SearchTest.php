<?php

namespace Naph\Searchlight\Tests\Eloquent\Feature;

use Naph\Searchlight\Tests\Eloquent\EloquentTestCase;
use Naph\Searchlight\Tests\TestModel;

class SearchTest extends EloquentTestCase
{
    /**
     * @test
     */
    public function match()
    {
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
