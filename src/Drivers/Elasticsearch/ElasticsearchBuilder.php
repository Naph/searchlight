<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchFields as Fields;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchBuilder implements Builder
{
    const ELASTICSEARCH_RANGE_OPERATORS = ['gt', 'gte', 'lte', 'lt'];

    protected $driver;

    protected $match = [];

    protected $filter = [];

    protected $range = [];

    protected $sort = [];

    protected $searchPrefix = false;

    protected $size = null;

    protected $withTrashed = false;

    /**
     * @var SearchlightContract[] $models
     */
    protected $models = [];

    function __construct(ElasticsearchDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param SearchlightContract $model
     */
    public function addModel(SearchlightContract $model)
    {
        $this->models[] = $model;
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
     * @return array
     * @throws SearchlightException
     */
    private function match()
    {
        $must = [];

        foreach ($this->match as $matchQuery) {
            $fields = [];

            if (is_null($matchQuery['fields']) && count($this->models) === 1) {
                try {
                    $fields = Fields::collect($this->models[0]->getSearchableFields());
                } catch (SearchlightException $e) {
                    throw new SearchlightException(sprintf('(%s): %s', get_class($this->models[0]), $e->getMessage()));
                }
            } elseif (is_array($matchQuery['fields'])) {
                $fields = Fields::collect($matchQuery['fields']);
            } elseif (is_string($matchQuery['fields'])) {
                $fields = Fields::collect([$matchQuery['fields']]);
            }

            if (is_string($matchQuery['query'])) {
                if (! trim($matchQuery['query'])) {
                    continue;
                }

                $must[] = $fields->queryString($matchQuery['query'], $this->searchPrefix);
            } elseif (is_array($matchQuery['query'])) {
                if (empty($matchQuery['query'])) {
                    continue;
                }

                $must[] = $fields->queryArray($matchQuery['query'], $this->searchPrefix);
            }
        }

        return compact('must');
    }

    /**
     * @return array
     */
    private function filter()
    {
        $must_not = [];
        $must = [];

        foreach ($this->filter as $term => $filterQuery) {
            if (is_null($filterQuery)) {
                $must_not[] = [
                    'exists' => ['field' => $term]
                ];
            } elseif ($filterQuery) {
                $must[] = [
                    (is_array($filterQuery) ? 'terms' : 'term') => [$term => $filterQuery]
                ];
            }
        }

        return array_filter(compact('must_not', 'must'));
    }

    /**
     * @return array
     */
    private function range()
    {
        $ranges = [];
        $must = [];

        foreach ($this->range as $rangeQuery) {
            $operator = self::ELASTICSEARCH_RANGE_OPERATORS[array_search($rangeQuery[1], self::RANGE_OPERATORS)];
            $ranges[$rangeQuery[0]][$operator] = $rangeQuery[2];
        }

        foreach ($ranges as $key => $value) {
            $must[] = ['range' => [$key => $value]];
        }

        return compact('must');
    }

    /**
     * @return array
     */
    public function sort()
    {
        return $this->sort;
    }

    /**
     * @return array
     */
    private function query(): array
    {
        return [
            'query' => [
                'bool' => array_merge_recursive($this->match(), $this->filter(), $this->range())
            ],
            'sort' => $this->sort()
        ];
    }

    /**
     * Set use of trashed index
     */
    public function withTrashed()
    {
        $this->withTrashed = true;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->match)
            && empty($this->filter)
            && empty($this->range);
    }

    /**
     * Eloquent builder
     *
     * @return EloquentBuilder
     * @throws \Exception
     */
    public function build(): EloquentBuilder
    {
        if (count($this->models) > 1) {
            throw new \Exception('Multiple model search does not support `builder`');
        }

        return $this->singleSearch();
    }

    /**
     * Eloquent collection
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return count($this->models) > 1
            ? $this->multiSearch()
            : $this->build()->get();
    }

    /**
     * Search-as-you-type results
     *
     * @return Collection
     */
    public function completion(): Collection
    {
        $this->searchPrefix = true;

        return $this->get();
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

    /**
     * @return EloquentBuilder
     */
    private function singleSearch(): EloquentBuilder
    {
        $model = $this->models[0];
        $indices = [$this->driver->getModelQuery($model)['index']];

        if ($this->withTrashed) {
            $indices[] = $this->driver->getModelQuery($model, true)['index'];
        }

        $results = $this->driver->connection->search([
            'size' => $this->size ?: $this->driver->getConfig('size'),
            'index' => $indices,
            'type' => $model->getSearchableType(),
            'body' => $this->query()
        ]);
        $documents = array_column($results['hits']['hits'], '_source');
        $documentIds = array_column($documents, 'id');
        $searchQuery = $model->whereIn($model->getKeyName(), $documentIds);

        if ($documentIds) {
            $searchQuery->orderByRaw('FIELD(id, '.implode(',', $documentIds).')', 'ASC');
        }

        if ($this->withTrashed) {
            $searchQuery->withTrashed();
        }

        return $searchQuery;
    }

    /**
     * @return Collection
     */
    private function multiSearch(): Collection
    {
        $contracts = [];
        $fields = [];
        $indices = [];

        foreach ($this->models as $model) {
            $contracts[$model->getSearchableType()] = $model;
            $fields = array_unique(array_merge($fields, $model->getSearchableFields()));
            $indices = array_unique(array_merge($indices, [$this->driver->getModelQuery($model)['index']]));

            if ($this->withTrashed) {
                $indices = array_unique(array_merge($indices, [$this->driver->getModelQuery($model, true)['index']]));
            }
        }

        foreach ($this->match as $key => $match) {
            $this->match[$key]['fields'] = $fields;
        }

        $searchResults = $this->driver->connection->search([
            'size' => $this->size ?: $this->driver->getConfig('size'),
            'index' => $indices,
            'type' => array_keys($contracts),
            'body' => $this->query()
        ]);

        $hits = collect($searchResults['hits']['hits']);
        $types = $hits->pluck('_type')->unique();

        foreach ($types as $type) {
            $typeResults = $hits->where('_type', $type);
            $typeIds = $typeResults->pluck('_id')->toArray();
            $modelQuery = $contracts[$type]->whereIn('id', $typeIds)
                ->orderByRaw('FIELD(id, '.implode(',', $typeIds).')', 'ASC');

            if ($this->withTrashed) {
                $modelQuery->withTrashed();
            }

            $models = $modelQuery->get();

            foreach ($typeResults as $pos => $typeResult) {
                if ($result = $models->where('id', $typeResult['_id'])->first()) {
                    $hits->put($pos, $result);
                } else {
                    $hits->forget($pos);
                }
            }
        }

        return $hits;
    }
}
