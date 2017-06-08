<?php

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Model\SearchlightContract;

interface Builder
{
    const RANGE_OPERATORS = ['>', '>=', '<=', '<'];

    public function addModel(SearchlightContract $model);

    public function addMatch(array $query);

    public function addFilter(array $query);

    public function addRange(array $query);

    public function isEmpty(): bool;

    public function withTrashed();

    public function build(): EloquentBuilder;

    public function get(): Collection;

    public function completion(): Collection;
}
