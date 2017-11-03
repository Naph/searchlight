<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Naph\Searchlight\Builder;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\SearchlightContract;

class ElasticsearchDriver extends Driver
{
    /**
     * @var \Elasticsearch\Client
     */
    public $connection;

    /**
     * ElasticsearchDriver constructor.
     * @param array $repositories
     * @param array $config
     * @throws SearchlightException
     */
    public function __construct(array $repositories, array $config)
    {
        parent::__construct($repositories, $config);

        $this->connection = ClientBuilder::create()->setHosts($config['hosts'])->build();

        if (! $this->config['index']) {
            throw new SearchlightException('Searchlight Exception: default index cannot be empty.');
        }
    }

    /**
     * @param SearchlightContract $model
     * @param bool $trashed
     *
     * @return array
     */
    public function getModelQuery(SearchlightContract $model, $trashed = false): array
    {
        return [
            'index' => ($model->getSearchableIndex() ?: $this->config['index']).($trashed ? '_trashed' : ''),
            'type' => $model->getSearchableType(),
            'id' => $model->getSearchableId(),
        ];
    }

    /**
     * @param SearchlightContract $model
     * @return void
     */
    public function index(SearchlightContract $model)
    {
        $document = array_merge(
            $this->getModelQuery($model, method_exists($model, 'trashed') && $model->trashed()),
            ['body' => $model->getSearchableBody()]
        );

        $this->connection->index($document);
    }

    /**
     * @param SearchlightContract $model
     * @return void
     */
    public function delete(SearchlightContract $model)
    {
        if (method_exists($model, 'trashed') && $model->trashed()) {
            $this->index($model);
        }

        try {
            $this->connection->delete($this->getModelQuery($model));
        } catch (Missing404Exception $exception) {
            // Delete if exists
        }
    }

    /**
     * @param SearchlightContract $model
     * @return void
     */
    public function restore(SearchlightContract $model)
    {
        $this->index($model);

        try {
            $this->connection->delete($this->getModelQuery($model, true));
        } catch (Missing404Exception $exception) {
            // Delete if exists
        }
    }

    /**
     * Delete all indices
     */
    public function deleteAll()
    {
        $indices = [];

        foreach ($this->repositories as $repository) {
            $model = new $repository();
            $indices[] = $this->getModelQuery($model)['index'];
            $indices[] = $this->getModelQuery($model, true)['index'];
        }

        foreach (array_unique($indices) as $index) {
            if ($this->connection->indices()->exists(compact('index'))) {
                $this->connection->indices()->delete(compact('index'));
            }
        }
    }

    /**
     * Return instance of driver's builder class
     *
     * @return Builder
     */
    public function builder(): Builder
    {
        return new ElasticsearchBuilder($this);
    }
}
