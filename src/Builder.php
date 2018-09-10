<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Naph\Searchlight\Model\{
    Decorator, SearchlightContract
};

abstract class Builder
{
    /**
     * Supported range operators
     */
    const RANGE_OPERATORS = ['>', '>=', '<=', '<'];

    /**
     * @var Decorator[] $models
     */
    protected $models = [];

    /**
     * @var array
     */
    protected $match = [];

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var array
     */
    protected $fuzzy = [];

    /**
     * @var array
     */
    protected $range = [];

    /**
     * @var array
     */
    protected $sort = [];

    /**
     * @var int|null
     */
    protected $size = null;

    /**
     * @var bool
     */
    protected $withTrashed = false;

    /**
     * @var Driver
     */
    protected $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Get builder instance
     *
     * @return EloquentBuilder
     */
    abstract public function build(): EloquentBuilder;

    /**
     * Get builder results
     *
     * @return Collection
     */
    abstract public function get(): Collection;

    /**
     * Get paginated builder results
     *
     * @param int $perPage
     * @param int $page
     *
     * @return LengthAwarePaginator
     */
    abstract public function paginate($perPage, $page): LengthAwarePaginator;

    /**
     * @param SearchlightContract $model
     */
    public function addModel(SearchlightContract $model)
    {
        $this->models = array_merge($this->driver->decorate($model), $this->models);
    }

    /**
     * @param array $match
     */
    public function addMatch(array $match)
    {
        $this->match[] = $match;
    }

    /**
     * @param array $filter
     */
    public function addFuzzy(array $filter)
    {
        $this->fuzzy = array_merge_recursive($this->fuzzy, $filter);
    }

    /**
     * @param array $filter
     */
    public function addFilter(array $filter)
    {
        $this->filter = array_merge_recursive($this->filter, $filter);
    }

    /**
     * @param array $query
     */
    public function addRange(array $query)
    {
        $this->range = array_merge_recursive($this->range, $query);
    }

    /**
     * @param array $query
     */
    public function addSort(array $query)
    {
        $this->sort = array_merge_recursive($this->sort, $query);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->match)
            && empty($this->filter)
            && empty($this->fuzzy)
            && empty($this->range)
            && empty($this->sort);
    }

    /**
     * Set use of trashed index
     */
    public function withTrashed(): Builder
    {
        $this->withTrashed = true;

        return $this;
    }

    /**
     * Returned result limit
     *
     * @param int $size
     * @return $this
     */
    public function size(int $size)
    {
        $this->size = $size;

        return $this;
    }
}
