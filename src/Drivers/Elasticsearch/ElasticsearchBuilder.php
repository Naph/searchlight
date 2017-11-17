<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchBuilder extends Builder
{
    const ELASTICSEARCH_RANGE_OPERATORS = ['gt', 'gte', 'lte', 'lt'];

    /**
     * @var ElasticsearchDriver
     */
    protected $driver;

    /**
     * @var ElasticsearchModel[]
     */
    protected $models = [];

    /**
     * Search-as-you-type flag
     *
     * @var bool
     */
    protected $searchPrefix = false;

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
                    $fields = ElasticsearchFields::collect($this->models[0]->getSearchableFields());
                } catch (SearchlightException $e) {
                    throw new SearchlightException(sprintf('(%s): %s', get_class($this->models[0]), $e->getMessage()));
                }
            } elseif (is_array($matchQuery['fields'])) {
                $fields = ElasticsearchFields::collect($matchQuery['fields']);
            } elseif (is_string($matchQuery['fields'])) {
                $fields = ElasticsearchFields::collect([$matchQuery['fields']]);
            }

            if (is_string($matchQuery['query'])) {
                if (!trim($matchQuery['query'])) {
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
     * Search-as-you-type enhanced get
     *
     * @return Collection
     */
    public function completion(): Collection
    {
        $this->searchPrefix = true;

        return $this->get();
    }

    /**
     * @return EloquentBuilder
     */
    private function singleSearch(): EloquentBuilder
    {
        $model = $this->models[0];
        $indices = [$model->getSearchableIndex()];

        if ($this->withTrashed) {
            $indices[] = $model->getTrashedIndex();
        }

        $results = $this->driver->connection()->search([
            'size' => $this->size ?: $this->driver->config('size'),
            'index' => $indices,
            'type' => $model->getSearchableType(),
            'body' => $this->query()
        ]);
        $documents = array_column($results['hits']['hits'], '_source');
        $documentIds = array_column($documents, 'id');

        return $this->convertQuery($model, $documentIds);
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
            $indices = array_unique(array_merge($indices, [$model->getSearchableIndex()]));

            if ($this->withTrashed) {
                $indices = array_unique(array_merge($indices, [$model->getTrashedIndex()]));
            }
        }

        foreach ($this->match as $key => $match) {
            $this->match[$key]['fields'] = $fields;
        }

        $searchResults = $this->driver->connection()->search([
            'size' => $this->size ?: $this->driver->config('size'),
            'index' => $indices,
            'type' => array_keys($contracts),
            'body' => $this->query(),
        ]);

        $hits = collect($searchResults['hits']['hits']);
        $types = $hits->pluck('_type')->unique();

        foreach ($types as $type) {
            $typeResults = $hits->where('_type', $type);
            $documentIds = $typeResults->pluck('_id')->toArray();
            $model = $contracts[$type];
            $models = $this->convertQuery($model, $documentIds)->get();

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

    /**
     * Find models by ids and preserves order
     *
     * @param ElasticsearchModel $model
     * @param array $ids
     *
     * @return EloquentBuilder
     */
    private function convertQuery($model, array $ids): EloquentBuilder
    {
        $query = $model->newQuery()->whereIn('id', $ids);

        if ($ids) {
            $statements = array_map(function ($index, $id) use ($model) {
                return "WHEN {$model->getKeyName()}={$id} THEN {$index}";
            }, array_keys($ids), $ids);

            $case = implode(' ', $statements);

            $query->orderByRaw("CASE {$case} END ASC");
        }

        if ($this->withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }
}
