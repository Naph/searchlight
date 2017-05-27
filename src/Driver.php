<?php

namespace Naph\Searchlight;

use Naph\Searchlight\Model\SearchlightContract;

interface Driver
{
    public function __construct(array $config);

    public function index(SearchlightContract $model);

    public function delete(SearchlightContract $model);

    public function deleteAll(string $index = '');

    public function builder(): Builder;
}
