<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Naph\Searchlight\Model\Decorator;
use Naph\Searchlight\Model\SearchlightContract;

abstract class Driver
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $repositories;

    /**
     * Optional model decorator class
     *
     * @var string
     */
    protected $decorator = Decorator::class;

    /**
     * Driver supports indexing
     * Toggles observation of index/delete/restore/flush events
     * Disable this to prevent bloating queue with unhandled jobs
     *
     * @var bool
     */
    public $supportsIndexing = true;

    /**
     * Driver constructor.
     *
     * @param array $repositories
     * @param array $config
     */
    public function __construct(array $repositories, array $config) {
        $this->config = $config;
        $this->repositories = $repositories;
    }

    /**
     * Retrieve driver config value
     * Uses dot notation
     *
     * @param $key
     * @return mixed
     */
    public function config(string $key)
    {
        return array_get($this->config, $key);
    }

    /**
     * @return array
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    /**
     * Return new instance of plugin builder
     *
     * @return Builder
     */
    abstract public function builder(): Builder;

    /**
     * Update search indices
     *
     * @param  Decorator[] $models
     * @return void
     */
    abstract protected function index(...$models): void;

    /**
     * Delete models indices
     *
     * @param Decorator[] $models
     * @return void
     */
    abstract protected function delete(...$models): void;

    /**
     * Restore deleted search indices
     *
     * @param Decorator[] $models
     * @return void
     */
    abstract protected function restore(...$models): void;

    /**
     * Flush indices of model type
     *
     * @param Decorator[] $models
     * @return void
     */
    abstract protected function flush(...$models): void;

    /**
     * Returns decorated models
     *
     * @param SearchlightContract[] $models
     * @return SearchlightContract[]
     */
    public function decorate(SearchlightContract ...$models): array
    {
        return array_map(function ($model) {
            return new $this->decorator($this, $model);
        }, $models);
    }

    /**
     * @param SearchlightContract[] $models
     */
    public function handleIndex(SearchlightContract ...$models): void
    {
        $this->index($this->decorate($models));
    }

    /**
     * @param SearchlightContract[] $models
     */
    public function handleDelete(SearchlightContract ...$models): void
    {
        $this->delete($this->decorate($models));
    }

    /**
     * @param SearchlightContract[] $models
     */
    public function handleRestore(SearchlightContract ...$models): void
    {
        $this->restore($this->decorate($models));
    }

    public function handleFlush(SearchlightContract ...$models): void
    {
        $this->flush($this->decorate($models));
    }
}
