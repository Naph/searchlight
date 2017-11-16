<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class Search
{
    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var Builder
     */
    protected $builder;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
        $this->builder = $driver->builder();
    }

    /**
     * Model/s to search
     * Supports multiple models
     *
     * @param SearchlightContract[] ...$models
     * @return Search
     * @throws SearchlightException
     */
    public function in(SearchlightContract ...$models): Search
    {
        foreach ($models as $model) {
            $this->builder->addModel($model);
        }

        return $this;
    }

    /**
     * Search by matching multiple fields with one query string
     *
     * @param string|array  $query
     * @param string|array|null  $fields
     *
     * @return Search
     */
    public function match($query, $fields = null): Search
    {
        if (! $query) {
            return $this;
        }

        $query = $this->driver->reduce($this, $query);
        $this->builder->addMatch(compact('query', 'fields'));

        return $this;
    }

    /**
     * Filter terms
     *
     * Filter array example:
     * $array = [
     *   'field' => 'query',
     *   'field' => null,
     *   ...
     * ];
     *
     * @param array $filter
     * @return Search
     *
     */
    public function filter(array $filter): Search
    {
        $this->builder->addFilter($filter);

        return $this;
    }

    /**
     * Filter by range
     *
     * Range array example:
     * $range = [
     *   ['field', '>', 'number'],
     *   ['field', '<', 'number'],
     *   ...
     * ];
     *
     * or as single:
     * $range = ['term', '>=', 'number'];
     *
     * @param array $range
     * @return Search
     * @throws \UnexpectedValueException
     */
    public function range(array $range): Search
    {
        $range = is_array(reset($range)) ? $range : [$range];

        foreach ($range as $key => $array) {
            if (! ($array[2] ?? false)) {
                unset($range[$key]);
            }

            if (! in_array($array[1], Builder::RANGE_OPERATORS)) {
                throw new \UnexpectedValueException("Range operator is not recognised: `{$array[1]}`");
            }
        }

        $this->builder->addRange($range);

        return $this;
    }

    /**
     *
     * Sort array examples
     * $sort = [
     *
     * as single
     *   'field' => 'asc|desc',
     *
     * or as array.
     *   'field' => [
     *     'mode' => 'min|max|sum|avg|median',
     *     'order' => 'asc|desc',
     *   ]
     *   ...
     * ];
     *
     * Additional array sort properties depend on driver used.
     *
     * @param array $sort
     * @return $this
     */
    public function sort(array $sort): Search
    {
        $this->builder->addSort($sort);

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->builder->isEmpty();
    }

    /**
     * Include trashed models in search
     *
     * @return $this
     */
    public function withTrashed(): Search
    {
        $this->builder->withTrashed();

        return $this;
    }

    /**
     * Convert to EloquentBuilder
     *
     * @return EloquentBuilder
     */
    public function builder(): EloquentBuilder
    {
        return $this->builder->build();
    }

    /**
     * Fetch results
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->builder->get();
    }

    /**
     * Fetch results using search-as-you-type completion
     *
     * @return Collection
     */
    public function completion(): Collection
    {
        return collect($this->builder->completion())->map(function (SearchlightContract $model) {
            foreach ($model->getSearchableFields() as $field => $boost) {
                return $model->$field;
            }

            return null;
        });
    }

    /**
     * Return fresh instance
     *
     * @return Search
     */
    public function newInstance(): Search
    {
        return new static($this->driver);
    }
}
