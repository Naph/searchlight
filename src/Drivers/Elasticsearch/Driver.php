<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Elasticsearch\{
    Client,
    ClientBuilder
};
use GuzzleHttp\Ring\Client\MockHandler;
use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\{
    Builder as SearchlightBuilder,
    Driver as SearchlightDriver
};

class Driver extends SearchlightDriver
{
    /**
     * @var string
     */
    protected $decorator = Document::class;

    /**
     * @var Client
     */
    private $connection;

    /**
     * @var MockHandler
     */
    public static $handler;

    /**
     * @var \Closure
     */
    private static $currentScrollResolver;

    /**
     * Return new or existing Elasticsearch connection
     *
     * @return Client
     * @throws SearchlightException
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

        try {
            $this->buildIndices();
        } catch (NoNodesAvailableException $e) {
            throw new SearchlightException('Unable to reach Elasticsearch node(s).');
        }

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
    public function builder(): SearchlightBuilder
    {
        return new Builder($this);
    }

    /**
     * Update search indices
     *
     * @param  Document[] $models
     *
     * @return void
     * @throws SearchlightException
     */
    protected function index($models): void
    {
        $this->bulk($models, function (Document $model) {
            return [
                ['index' => $model->metadata($model->isSoftDeleted())],
                $model->body(),
            ];
        });
    }

    /**
     * Delete model documents
     *
     * @param Document[] $models
     *
     * @return void
     * @throws SearchlightException
     */
    protected function delete($models): void
    {
        $this->bulk($models, function (Document $model) {
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
     * @param Document[] $models
     *
     * @return void
     * @throws SearchlightException
     */
    protected function restore($models): void
    {
        $this->bulk($models, function (Document $model) {
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
     * @param Document[] $models
     *
     * @return void
     * @throws SearchlightException
     */
    protected function flush($models): void
    {
        foreach ($models as $model) {
            $mapping = [
                'index' => $model->getSearchableIndex(),
            ];

            if ($this->connection()->indices()->exists($mapping)) {
                $this->connection()->indices()->delete($mapping);
            }
        }
    }

    /**
     * @param Document[] $models
     * @param \Closure $metadata
     *
     * @return void
     * @throws SearchlightException
     */
    protected function bulk($models, \Closure $metadata): void
    {
        $query = collect();

        foreach ($models as $model) {
            $query = $query->merge($metadata($model));
        }

        foreach ($query->chunk(840) as $body) {
            $this->connection()->bulk([
                'body' => $body->toArray(),
            ]);
        }
    }

    /**
     * @throws NoNodesAvailableException
     * @throws SearchlightException
     */
    private function buildIndices()
    {
        try {
            $indices = $this->connection()->indices()->get([
                'index' => '_all'
            ]);
        } catch (NoNodesAvailableException $e) {
            throw $e;
        }

        foreach ($this->repositories as $repository) {
            $mapping = [];
            $model = new Document($this, new $repository());
            $index = $model->getSearchableIndex();

            if (!isset($indices[$index])) {
                $this->connection()->indices()->create(compact('index'));
                $mapping = $model->mapping();
            } elseif (!isset($indices[$index]['mappings'][$model->getSearchableType()])) {
                $mapping = $model->mapping();
            }

            if (!empty($mapping)) {
                $this->connection()->indices()->putMapping([
                    'index' => $model->getSearchableIndex(),
                    'type' => $model->getSearchableType(),
                    'body' => $mapping,
                ]);
            }
        }
    }

    /**
     * Resolve the current batch or return the default value.
     *
     * @return string
     */
    static public function resolveCurrentScroll()
    {
        if (isset(static::$currentScrollResolver)) {
            return call_user_func(static::$currentScrollResolver);
        }

        return null;
    }

    /**
     * Set the current scroll resolver callback
     *
     * @param \Closure $resolver
     */
    static public function currentScrollResolver(\Closure $resolver)
    {
        static::$currentScrollResolver = $resolver;
    }
}
