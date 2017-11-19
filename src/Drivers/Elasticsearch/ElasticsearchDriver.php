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
        $stream = tmpfile();
        fwrite($stream, json_encode($body));
        fseek($stream, 0);

        self::$handler = new MockHandler([
            'status' => 200,
            'transfer_stats' => [
                'total_time' => 2000,
            ],
            'body' => $stream,
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
     * Delete model documents
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
     * Flush all models of type
     *
     * @param ElasticsearchModel[] $models
     * @return void
     */
    protected function flush(...$models): void
    {
        foreach ($models as $model) {
            $this->connection()->indices()->deleteMapping([
                'index' => '_all',
                'type' => $model->getSearchableType(),
            ]);
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
