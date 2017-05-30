<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchFields as Fields;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchBuilder implements Builder
{
    const ELASTICSEARCH_RANGE_OPERATORS = ['gt', 'gte', 'lte', 'lt'];

    protected $driver;

    protected $match = [];

    protected $filter = [];

    protected $range = [];

    /**
     * @var SearchlightContract[] $models
     */
    protected $models = [];

    function __construct(ElasticsearchDriver $driver)
    {
        $this->driver = $driver;
    }

    public function addModel(SearchlightContract $model)
    {
        $this->models[] = $model;
    }

    public function addMatch(array $query)
    {
        $this->match[] = $query;
    }

    public function addFilter(array $query)
    {
        $this->filter[] = $query;
    }

    public function addRange(array $query)
    {
        $this->range[] = $query;
    }

    private function match()
    {
        $must = [];

        foreach ($this->match as $matchQuery) {
            $fields = [];

            if (is_null($matchQuery['fields']) && count($this->models) === 1) {
                $fields = Fields::collect($this->models[0]->getSearchableFields());
            } elseif (is_array($matchQuery['fields'])) {
                $fields = Fields::collect($matchQuery['fields']);
            } elseif (is_string($matchQuery['fields'])) {
                $fields = Fields::collect([$matchQuery['fields']]);
            }

            if (is_string($matchQuery['query'])) {
                $must[] = $fields->queryString($matchQuery['query']);
            } elseif (is_array($matchQuery['query'])) {
                $must[] = $fields->queryArray($matchQuery['query']);
            }
        }

        return compact('must');
    }

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

    private function range()
    {
        $must = [];

        foreach ($this->range as $rangeQuery) {
            $operator = self::ELASTICSEARCH_RANGE_OPERATORS[array_search($rangeQuery[1], self::RANGE_OPERATORS)];
            $must[] = [
                'range' => [$rangeQuery[0] => [$operator => $rangeQuery[2]]]
            ];
        }

        return compact('must');
    }

    private function query(): array
    {
        return [
            'query' => [
                'bool' => array_merge_recursive($this->match(), $this->filter(), $this->range())
            ]
        ];
    }

    public function build(): EloquentBuilder
    {
        if (count($this->models) > 1) {
            throw new \Exception('Multiple model search does not support `builder`');
        }

        return $this->singleSearch();
    }

    public function get(): Collection
    {
        return count($this->models) > 1
            ? $this->multiSearch()
            : $this->build()->get();
    }

    private function singleSearch(): EloquentBuilder
    {
        $model = reset($this->models);

        $results = $this->driver->connection->search([
            'size' => $this->driver->config['size'],
            'index' => $model->getSearchableIndex() ?: $this->driver->config['index'],
            'type' => $model->getSearchableType(),
            'body' => $this->query()
        ]);
        $documents = array_column($results['hits']['hits'], '_source');
        $documentIds = array_column($documents, 'id');
        $searchQuery = $model->whereIn($model->getKeyName(), $documentIds);

        if ($documentIds) {
            $searchQuery->orderBy(DB::raw('FIELD(id, '.implode(',', $documentIds).')'), 'ASC');
        }

        return $searchQuery;
    }

    private function multiSearch(): Collection
    {
        $contracts = [];
        $fields = [];

        foreach ($this->models as $model) {
            $contracts[$model->getSearchableType()] = $model;
            $searchFields = $model->getSearchableFields();
            arsort($searchFields);
            $searchField = array_shift($searchFields);
            $fields[] = array_keys($searchField)[0].'^5';
        }

        $query = [];

        foreach ($this->match as $matchQuery) {
            if (is_string($matchQuery['query'])) {
                $query = array_merge($query, [$matchQuery['query']]);
            } elseif (is_array($matchQuery['query'])) {
                $query = array_merge($query, $matchQuery['query']);
            }
        }

        $searchResults = $this->driver->connection->search([
            'size' => 100,
            'index' => '_all',
            'type' => array_keys($contracts),
            'body' => [
                'query' => Fields::collect(array_merge($fields, '_all'))->queryArray($query)
            ]
        ]);
        $hits = collect($searchResults['hits']['hits']);
        $types = $hits->pluck('_type')->unique();

        foreach ($types as $type) {
            $typeResults = $hits->where('_type', $type);
            $typeIds = $typeResults->pluck('_id')->toArray();
            $modelQuery = $contracts[$type]->whereIn('id', $typeIds)
                ->orderBy(DB::raw('FIELD(id, '.implode(',', $typeIds).')'), 'ASC')
                ->get();

            foreach ($typeResults as $pos => $typeResult) {
                $hits->put($pos, $modelQuery->where('id', $typeResult['_id'])->first());
            }
        }

        return $hits;
    }
}
