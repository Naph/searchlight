<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Support\Facades\Config;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchDriver extends Driver
{
    public $config;

    public $connection;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connection = ClientBuilder::create()->setHosts($config['hosts'])->build();

        if (! $this->config['index']) {
            throw new SearchlightException('Searchlight Exception: default index cannot be empty.');
        }
    }

    public function index(SearchlightContract $model)
    {
        $document = [
            'index' => method_exists($model, 'trashed') && $model->trashed()
                ? $model->getSearchableTrashedIndex()
                : $model->getSearchableIndex(),
            'type' => $model->getSearchableType(),
            'id' => $model->getSearchableId(),
            'body' => $model->getSearchableBody()
        ];

        $this->connection->index($document);
    }

    public function delete(SearchlightContract $model)
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            $this->connection->reindex([
                'source' => [
                    'index' => $model->getSearchableIndex(),
                    'type' => $model->getSearchableType(),
                    'id' => $model->getSearchableId(),
                ],
                'dest' => [
                    'index' => $model->getSearchableTrashedIndex()
                ]
            ]);
        }

        try {
            $this->connection->delete([
                'index' => $model->getSearchableIndex(),
                'type' => $model->getSearchableType(),
                'id' => $model->getSearchableId(),
            ]);
        } catch (Missing404Exception $exception) {
            // Delete if exists
        }
    }

    public function restore(SearchlightContract $model)
    {
        $this->index($model);

        try {
            $this->connection->delete([
                'index' => $model->getSearchableTrashedIndex(),
                'type' => $model->getSearchableType(),
                'id' => $model->getSearchableId(),
            ]);
        } catch (Missing404Exception $exception) {
            // Delete if exists
        }
    }

    public function deleteAll()
    {
        $indices = [];

        foreach (Config::get('searchlight.repositories') as $repository) {
            $index = (new $repository())->getSearchableIndex();
            $indices[] = $index;
            $indices[] = $index.'_trashed';
        }

        foreach (array_unique($indices) as $index) {
            if ($this->connection->indices()->exists(compact('index'))) {
                $this->connection->indices()->delete(compact('index'));
            }
        }
    }

    public function builder(): Builder
    {
        return new ElasticsearchBuilder($this);
    }
}
