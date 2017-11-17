<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\{
    Client, ClientBuilder
};
use GuzzleHttp\Ring\Client\MockHandler;
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
     * @var Client
     */
    private $connection;

    /**
     * @var MockHandler
     */
    public static $handler;

    /**
     * Return new or existing Elasticsearch connection
     *
     * @return Client
     */
    public function connection()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $builder = ClientBuilder::create()->setHosts($this->config('hosts'));

        if (self::$handler) {
            $builder->setHandler(self::$handler);
        }

        $this->connection = $builder->build();

        return $this->connection;
    }

    /**
     * Set expected result from Elasticsearch
     *
     * @param array $body
     */
    public static function setMockedResponse(array $body)
    {
        $temp = tmpfile();
        fwrite($temp, json_encode($body));
        fseek($temp, 0);

        self::$handler = new MockHandler([
            'status' => 200,
            'transfer_stats' => [
                'total_time' => 2000,
            ],
            'body' => $temp,
        ]);
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
