<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function addMatch(array $match)
    {
        $this->match[] = $match;
    }

    public function addFilter(array $filter)
    {
        $this->filter = array_merge_recursive($this->filter, $filter);
    }

    public function addRange(array $query)
    {
        $this->range = array_merge_recursive($this->range, $query);
    }

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

                $must[] = $fields->queryString($matchQuery['query']);
            } elseif (is_array($matchQuery['query'])) {
                if (empty($matchQuery['query'])) {
                    continue;
                }

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

    public function isEmpty(): bool
    {
        return empty($this->match)
            && empty($this->filter)
            && empty($this->range);
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
            $fields = array_unique(array_merge($fields, $model->getSearchableFields()));
        }

        foreach ($this->match as $key => $match) {
            $this->match[$key]['fields'] = $fields;
        }

        $searchResults = $this->driver->connection->search([
            'size' => 500,
            'index' => '_all',
            'type' => array_keys($contracts),
            'body' => $this->query()
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
                if ($result = $modelQuery->where('id', $typeResult['_id'])->first()) {
                    $hits->put($pos, $result);
                } else {
                    $hits->forget($pos);
                }
            }
        }

        return $hits;
    }
}
