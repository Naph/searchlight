<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Naph\Searchlight\Builder as SearchlightBuilder;
use Naph\Searchlight\Exceptions\SearchlightException;

class Builder extends SearchlightBuilder
{
    use Documents;

    const ELASTICSEARCH_RANGE_OPERATORS = ['gt', 'gte', 'lte', 'lt'];

    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var int
     */
    protected $from;

    /**
     * @var string
     */
    static protected $scrollId;

    /**
     * Eloquent builder
     *
     * @return EloquentBuilder
     * @throws SearchlightException
     */
    public function build(): EloquentBuilder
    {
        $this->models = array_slice($this->models, 0, 1);
        $results = $this->search();
        $hits = collect($results['hits']['hits']);
        $ids = $hits->pluck('_id')->toArray();

        return $this->queryWithOrdinality($this->documents()->first(), $ids);
    }

    /**
     * Eloquent collection
     *
     * @return Collection
     * @throws SearchlightException
     */
    public function get(): Collection
    {
        return $this->hydrateResults($this->search());
    }

    /**
     * @param int $perPage
     * @param int $page
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws SearchlightException
     */
    public function paginate($perPage, $page): LengthAwarePaginator
    {
        self::$scrollId = Driver::resolveCurrentScroll();

        $this->from = $perPage * ($page - 1);
        $this->size = $perPage;

        try {
            $items = $this->hydrateResults($this->search());
        } catch (Missing404Exception $e) {
            $items = collect();
        }

        return new Paginator(
            $items,
            $items->count(),
            $perPage,
            $page,
            ['scrollId' => self::$scrollId]
        );
    }

    /**
     * @return array
     */
    protected function match()
    {
        $must = [];

        foreach ($this->match as $match) {
            /**
             * @var string $query
             * @var string[] $fields
             */
            list($query, $fields) = $match;

            $must[] = [
                'simple_query_string' => [
                    'fields' => $fields ?: $this->searchableFields(),
                    'query' => trim($query),
                    'analyze_wildcard' => true,
                    'lenient' => true,
                ],
            ];
        }

        return compact('must');
    }

    /**
     * @return array
     */
    protected function filter()
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
    protected function fuzzy()
    {
        $must = [];

        foreach ($this->fuzzy as $term => $query) {
            $must[] = ['fuzzy' => [$term => $query]];
        }

        return compact('must');
    }

    /**
     * @return array
     */
    protected function range()
    {
        $ranges = [];
        $must = [];

        foreach ($this->range as $query) {
            $operator = self::ELASTICSEARCH_RANGE_OPERATORS[array_search($query[1], self::RANGE_OPERATORS)];
            $ranges[$query[0]][$operator] = $query[2];
        }

        foreach ($ranges as $key => $value) {
            $must[] = ['range' => [$key => $value]];
        }

        return compact('must');
    }

    /**
     * @return array
     * @throws SearchlightException
     */
    private function search(): array
    {
        $params = [
            'size' => $this->size ?: $this->driver->config('size'),
            'index' => $this->searchableIndices($this->withTrashed),
            'type' => $this->searchableTypes(),
            'body' => [
                'query' => [
                    'bool' => array_merge_recursive(
                        $this->match(),
                        $this->filter(),
                        $this->fuzzy(),
                        $this->range()
                    ),
                ],
                'sort' => $this->sort,
            ],
        ];

        if ($this->from !== null) {
            $params += [
                'scroll' => '3m',
                'from' => $this->from,
            ];
        }

        if ($scroll_id = Driver::resolveCurrentScroll()) {
            return $this->driver->connection()->scroll(compact('scroll_id'));
        }

        $results = $this->driver->connection()->search($params);

        if ($scrollId = array_get($results, '_scroll_id')) {
            self::$scrollId = $scrollId;
        }

        return $results;
    }

    /**
     * Find models by ids and preserve order
     *
     * @param Document $model
     * @param array $ids
     *
     * @return EloquentBuilder
     */
    private function queryWithOrdinality(Document $model, array $ids): EloquentBuilder
    {
        $query = $model->newQuery()->whereIn($model->getPrimaryKeyName(), $ids);

        if ($ids) {
            $statements = array_map(function ($index, $id) use ($model) {
                return "WHEN {$model->getPrimaryKeyName()}=\"{$id}\" THEN {$index}";
            }, array_keys($ids), $ids);

            $case = implode(' ', $statements);

            $query->orderByRaw("CASE {$case} END ASC");
        }

        if ($this->withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * @param array $results
     *
     * @return Collection
     */
    private function hydrateResults(array $results): Collection
    {
        $hits = collect($results['hits']['hits'])->keyBy('_id');

        foreach ($this->documents() as $model) {
            $fresh = $model->newQueryWithoutScopes()
                ->whereIn($model->getKeyName(), $hits->where('_type', $model->getSearchableType())->pluck('_id'))
                ->when($this->withTrashed, function ($query) {
                    $query->withTrashed();
                })
                ->get()
                ->getDictionary();

            $hits = $hits->merge($fresh);
        }

        return $hits->filter(function ($value) {
            return ! is_array($value);
        })->values();
    }
}
