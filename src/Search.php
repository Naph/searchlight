<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class Search
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var SearchlightContract[]
     */
    protected $models = [];

    /**
     * @var array
     */
    protected $matches = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $ranges = [];

    /**
     * @var array
     */
    protected $sorts = [];

    /**
     * @var bool
     */
    protected $withTrashed = false;

    /**
     * @var int|null
     */
    protected $take = null;

    /**
     * Search constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->driver();
    }

    /**
     * @param \Naph\Searchlight\Driver $driver
     */
    protected function setDriver(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Set the driver to use
     *
     * @param string|null $name
     *
     * @return \Naph\Searchlight\Search
     */
    public function driver(string $name = null): Search
    {
        $this->setDriver($this->app['searchlight']->driver($name));

        return $this;
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
        $this->models = array_merge($this->models, $models);

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

        $this->matches[] = compact('query', 'fields');

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
        $this->filters[] = $filter;

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

        $this->ranges[] = $range;

        return $this;
    }

    /**
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
        $this->sorts[] = $sort;

        return $this;
    }

    /**
     * Include trashed models in search
     *
     * @return $this
     */
    public function withTrashed(): Search
    {
        $this->withTrashed = true;

        return $this;
    }

    /**
     * @param int $count
     *
     * @return Search
     */
    public function take(int $count): Search
    {
        $this->take = $count;

        return $this;
    }

    /**
     * Finalise the builder and return
     *
     * @return \Naph\Searchlight\Builder
     */
    protected function builder(): Builder
    {
        $builder = $this->driver->builder();

        foreach ($this->models as $model) {
            $builder->addModel($model);
        }

        foreach ($this->matches as $match) {
            $match['query'] = $this->app['searchlight']->reduce($this, $match['query']);
            $builder->addMatch($match);
        }

        foreach ($this->filters as $filter) {
            $builder->addFilter($filter);
        }

        foreach ($this->ranges as $range) {
            $builder->addRange($range);
        }

        foreach ($this->sorts as $sort) {
            $builder->addSort($sort);
        }

        if ($this->withTrashed) {
            $builder->withTrashed();
        }

        if ($this->take) {
            $builder->size($this->take);
        }

        return $builder;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->matches)
            && empty($this->filters)
            && empty($this->ranges)
            && empty($this->sorts);
    }

    /**
     * Convert to EloquentBuilder
     *
     * @return EloquentBuilder
     */
    public function eloquent(): EloquentBuilder
    {
        return $this->builder()->build();
    }

    /**
     * Fetch results
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->builder()->get();
    }

    /**
     * Fetch results using search-as-you-type completion
     *
     * @return Collection
     */
    public function completion(): Collection
    {
        return collect($this->builder()->completion())->map(function (SearchlightContract $model) {
            foreach ($model->getSearchableFields() as $field => $boost) {
                return $model->{$field === 0 ? $boost : $field};
            }

            return null;
        });
    }

    /**
     * Return fresh instance
     *
     * @return Search
     */
    public function newQuery(): Search
    {
        return new static($this->app);
    }
}
