<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Pagination\LengthAwarePaginator;

class Paginator extends LengthAwarePaginator
{
    /**
     * @var string
     */
    protected $scrollId;

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), ['scroll_id' => $this->scrollId]);
    }
}
