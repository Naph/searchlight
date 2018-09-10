<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Naph\Searchlight\Model\SearchlightContract;

class Search
{
    /**
     * @var Container
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
    protected $fuzzy = [];

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
     * @var \Closure
     */
    protected static $currentBatchResolver;

    /**
     * Search constructor.
     *
     * @param Container $app
     */
    public function __construct(Container $app)
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
     * @return Search
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
     * @param SearchlightContract[] $models
     *
     * @return Search
     */
    public function in(SearchlightContract ...$models): Search
    {
        $this->models = array_merge($this->models, $models);

        return $this;
    }

    /**
     * Search by matching multiple fields with one query string
     *
     * @param string  $query
     * @param array  $fields
     *
     * @return Search
     */
    public function match(string $query, array $fields = []): Search
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
     * $filter = [
     *   'field' => 'query',
     *   'field' => null,
     *   ...
     * ];
     *
     * @param array $filter
     *
     * @return Search
     */
    public function filter(array $filter): Search
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Fuzzy terms
     *
     * Fuzzy array example:
     * $fuzzy = [
     *   'field' => 'query',
     *   ...
     * ];
     *
     * @param array $fuzzy
     *
     * @return Search
     */
    public function fuzzy(array $fuzzy): Search
    {
        $this->fuzzy[] = $fuzzy;

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
     * @param array ...$range
     *
     * @return Search
     */
    public function range(array ...$range): Search
    {
        foreach ($range as $key => $array) {
            if (! ($array[2] ?? false)) {
                continue;
            }

            if (! in_array($array[1], Builder::RANGE_OPERATORS)) {
                throw new \UnexpectedValueException("Range operator is not recognised: `{$array[1]}`");
            }

            $this->ranges[] = $array;
        }

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
     * @return Builder
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

        foreach ($this->fuzzy as $fuzzy) {
            $builder->addFuzzy($fuzzy);
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
            && empty($this->fuzzy)
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
     * Fetch paginated results
     *
     * @param int $perPage
     * @param string $pageName
     * @param null|int $page
     *
     * @return Collection
     */
    public function paginate(int $perPage = 15, string $pageName = 'page', ?int $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        return $this->builder()->paginate($perPage, $page);
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
