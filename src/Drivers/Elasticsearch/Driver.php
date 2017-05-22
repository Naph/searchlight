<?php
declare(strict_types=1);

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Curl\CouldNotResolveHostException;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Naph\Searchlight\SearchlightBuilder;
use Naph\Searchlight\SearchlightContract;
use Naph\Searchlight\SearchlightDriver;

class Driver implements SearchlightDriver
{
    protected $index;

    protected $config;

    protected $connection;

    public function __construct(string $index, array $config)
    {
        $this->index = $index;
        $this->config = $config;
        $this->connection = ClientBuilder::create()->setHosts($config['hosts'])->build();
        try {
            $this->connection->ping();
        } catch (\Exception $e) {
            throw new CouldNotResolveHostException();
        }
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function index(SearchlightContract $model)
    {
        $body = $model->getSearchableBody();

        foreach ($body as $key => $value) {
            if (is_bool($value)) {
                $body[$key] = (int) $value;
            }
        }

        if ($model->indexValidates()) {
            $this->connection->index([
                'index' => $model->getSearchableIndex(),
                'type' => $model->getSearchableType(),
                'id' => $model->getSearchableId(),
                'body' => $body
            ]);
        } else {
            $this->delete($model);
        }
    }

    public function deleteAll(string $index = '')
    {
        if (! $index) {
            $index = $this->config['index'];
        }

        if ($this->connection->indices()->exists(compact('index'))) {
            $this->connection->indices()->delete(compact('index'));
        }
    }

    public function delete(SearchlightContract $model)
    {
        try {
            $this->deleteDocument(
                $model->getSearchableIndex(),
                $model->getSearchableType(),
                $model->getSearchableId()
            );
        } catch (Missing404Exception $exception) {
            Log::warning($exception->getMessage());
        }
    }

    protected function deleteDocument($index, $type, $id)
    {
        $this->connection->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id
        ]);
    }

    public function buildQuery(SearchlightBuilder $queryBuilder): array
    {
        return Query::create($queryBuilder)->build();
    }

    /**
     * Experimental
     *
     * @param SearchlightContract[] $models
     * @param string $query
     *
     * @return Collection
     */
    public function multi($models, $query): Collection
    {
        $contracts = [];
        $fields = [];

        foreach ($models as $model) {
            $contracts[$model->getSearchableType()] = $model;
            $searchableFields = $model->getSearchableFields();
            arsort($searchableFields);
            $searchableField = array_shift($searchableFields);
            $fields[] = array_keys($searchableField)[0].'^5';
        }

        $searchResults = $this->connection->search([
            'size' => 100,
            'index' => '_all',
            'type' => array_keys($contracts),
            'body' => [
                'query' => [
                    'simple_query_string' => [
                        'query' => $query,
                        'fields' => array_merge($fields, ['_all'])
                    ]
                ]
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
                /** @var SearchlightContract $model */
                $model = $modelQuery->where('id', $typeResult['_id'])->first();

                if (! $model || ! $model->indexValidates()) {
                    $hits->forget($pos);
                    $this->deleteDocument($typeResult['_index'], $typeResult['_type'], $typeResult['_id']);
                } else {
                    $hits->put($pos, $model);
                }
            }
        }

        return $hits;
    }

    public function search(SearchlightContract $model, $query): Builder
    {
        $results = $this->baseSearch($model, $query);
        $documents = array_column($results['hits']['hits'], '_source');
        $documentIds = array_column($documents, 'id');
        $searchQuery = $model->whereIn($model->getKeyName(), $documentIds);

        if ($documentIds) {
            $searchQuery->orderBy(DB::raw('FIELD(id, '.implode(',', $documentIds).')'), 'ASC');
        }

        return $searchQuery;
    }

    private function baseSearch(SearchlightContract $model, array $query)
    {
        return $this->connection->search([
            'size' => Config::get('searchlight.size'),
            'index' => $model->getSearchableIndex(),
            'type' => $model->getSearchableType(),
            'body' => $query
        ]);
    }
}
