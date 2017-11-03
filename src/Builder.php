<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Model\SearchlightContract;

interface Builder
{
    /**
     * Supported range operators
     */
    const RANGE_OPERATORS = ['>', '>=', '<=', '<'];

    /**
     * @param SearchlightContract $model
     * @return mixed
     */
    public function addModel(SearchlightContract $model);

    /**
     * @param array $query
     * @return mixed
     */
    public function addMatch(array $query);

    /**
     * @param array $query
     * @return mixed
     */
    public function addFilter(array $query);

    /**
     * @param array $query
     * @return mixed
     */
    public function addRange(array $query);

    /**
     * @param array $query
     * @return mixed
     */
    public function addSort(array $query);

    /**
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * @return mixed
     */
    public function withTrashed();

    /**
     * @return EloquentBuilder
     */
    public function build(): EloquentBuilder;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @return Collection
     */
    public function completion(): Collection;

    /**
     * Set the result set size
     *
     * @param int $size
     * @return static
     */
    public function size(int $size);
}
