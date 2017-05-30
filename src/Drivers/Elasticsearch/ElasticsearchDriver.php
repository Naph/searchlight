<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchDriver implements Driver
{
    public $config;

    public $connection;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connection = ClientBuilder::create()->setHosts($config['hosts'])->build();

        if (! $this->connection->info()) {
            throw new SearchlightException('Elasticsearch host could not be resolved.');
        }

        if (! $this->config['index']) {
            throw new SearchlightException('Searchlight Exception: default index cannot be empty.');
        }
    }

    public function index(SearchlightContract $model)
    {
        $body = $model->getSearchableBody();

        foreach ($body as $key => $value) {
            if (is_bool($value)) {
                $body[$key] = (int) $value;
            }
        }

        $this->connection->index([
            'index' => $model->getSearchableIndex() ?: $this->config['index'],
            'type' => $model->getSearchableType(),
            'id' => $model->getSearchableId(),
            'body' => $body
        ]);
    }

    public function delete(SearchlightContract $model)
    {
        try {
            $this->deleteDocument(
                $model->getSearchableIndex() ?: $this->config['index'],
                $model->getSearchableType(),
                $model->getSearchableId()
            );
        } catch (Missing404Exception $exception) {
            // Delete if exists
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

    protected function deleteDocument($index, $type, $id)
    {
        $this->connection->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id
        ]);
    }

    public function builder(): Builder
    {
        return new ElasticsearchBuilder($this);
    }
}
