<?php

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class Search
{
    protected $driver;

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
     * @param array ...$models
     * @return Search
     * @throws SearchlightException
     */
    public function in(...$models): Search
    {
        foreach ($models as $model) {
            if (! $model instanceof SearchlightContract) {
                throw new SearchlightException(
                    sprintf('Argument passed to %s (%s) must implement interface %s', self::class, get_class($model), SearchlightContract::class)
                );
            }

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
     *  Filter array example:
     *  $array = [
     *    'field' => 'query',
     *    'field' => null,
     *    ...
     *  ];
     *
     * @param array $array
     *
     * @return Search
     */
    public function filter(array $array): Search
    {
        $this->builder->addFilter($array);

        return $this;
    }

    /**
     * Filter by range
     *
     * Range array example:
     * $array = [
     *   ['field', '>', 'number'],
     *   ['field', '<', 'number'],
     *   ...
     * ];
     *
     * or as single:
     * $array = ['term', '>=', 'number'];
     *
     * @param array $array
     *
     * @throws \UnexpectedValueException
     * @return Search
     */
    public function range(array $array): Search
    {
        $range = is_array(reset($array)) ? $array : [$array];

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
    public function withTrashed()
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
        return collect($this->builder->completion())->map(function(SearchlightContract $model) {
            try {
                return $model->{(new Fields($model->getSearchableFields()))->first()};
            } catch (SearchlightException $e) {
                throw new SearchlightException(sprintf('(%s): %s', get_class($model), $e->getMessage()));
            }
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
