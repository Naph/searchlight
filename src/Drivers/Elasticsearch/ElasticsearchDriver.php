<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\{
    Builder, Driver
};

class ElasticsearchDriver extends Driver
{
    /**
     * @var string
     */
    protected $decorator = ElasticsearchModel::class;

    /**
     * @var \Elasticsearch\Client
     */
    public $connection;

    /**
     * ElasticDriver constructor.
     *
     * @param array $repositories
     * @param array $config
     * @throws SearchlightException
     */
    public function __construct(array $repositories, array $config)
    {
        parent::__construct($repositories, $config);

        $this->connection = ClientBuilder::create()->setHosts($this->config('hosts'))->build();

        if (! $this->config('index')) {
            throw new SearchlightException('Searchlight Exception: default index cannot be empty.');
        }
    }

    /**
     * Return new instance of plugin builder
     *
     * @return Builder
     */
    public function builder(): Builder
    {
        return new ElasticsearchBuilder($this);
    }

    /**
     * Update search indices
     *
     * @param  ElasticsearchModel[] ...$models
     * @return void
     */
    protected function index(...$models): void
    {
        $this->bulk($models, function (ElasticsearchModel $model) {
            return [
                ['index' => $model->metadata()],
                $model->body(),
            ];
        });
    }

    /**
     * Delete search indices
     *
     * @param ElasticsearchModel[] ...$models
     * @return void
     */
    protected function delete(...$models): void
    {
        $this->bulk($models, function (ElasticsearchModel $model) {
            $actions = [
                ['delete' => $model->metadata()],
            ];

            if ($model->softDeletes()) {
                array_push($actions,
                    ['index' => $model->metadata(true)],
                    $model->body()
                );
            }

            return $actions;
        });
    }

    /**
     * Restore deleted search indices
     *
     * @param ElasticsearchModel[] ...$models
     * @return void
     */
    protected function restore(...$models): void
    {
        $this->bulk($models, function (ElasticsearchModel $model) {
            return [
                ['delete' => $model->metadata(true)],
                ['index' => $model->metadata()],
                $model->body(),
            ];
        });
    }

    /**
     * Flush indices of model type
     *
     * @param ElasticsearchModel[] $models
     * @return void
     */
    protected function flush(...$models): void
    {
        $indices = [];

        foreach ($models as $model) {
            array_push($indices,
                $model->getSearchableIndex(),
                $model->getTrashedIndex()
            );
        }

        foreach (array_unique($indices) as $index) {
            if ($this->connection->indices()->exists(compact('index'))) {
                $this->connection->indices()->delete(compact('index'));
            }
        }
    }

    /**
     * @param ElasticsearchModel[] $models
     * @param \Closure $metadata
     * @return void
     */
    protected function bulk($models, \Closure $metadata): void
    {
        $query = [];

        foreach ($models as $model) {
            array_push($query, ...$metadata($model));
        }

        $this->connection->bulk($query);
    }
}