<?php

namespace Naph\Searchlight\Tests;

use Illuminate\Database\Eloquent\Model;
use Naph\Searchlight\Model\SearchlightContract;
use Naph\Searchlight\Model\SearchlightTrait;

class TestModel extends Model implements SearchlightContract
{
    use SearchlightTrait;

    protected $fillable = [
        'name',
        'email',
        'location',
    ];

    /**
     * Searchable properties
     *
     * @return array
     */
    public function getSearchableFields(): array
    {
        return [
            'name' => 0.6,
            'email' => 0.8,
            'location',
        ];
    }
}
