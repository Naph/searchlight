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
    protected $reducers;

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
     * Driver constructor.
     *
     * @param array $repositories
     * @param array $config
     */
    public function __construct(array $repositories, array $config) {
        $this->config = $config;
        $this->reducers = [];
        $this->repositories = $repositories;
    }

    /**
     * Retrieve driver config value
     * Uses dot notation
     *
     * @param $key
     * @return mixed
     */
    public function config(string $key): mixed
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
        if (!$this->decorator) {
            return $models;
        }

        return array_map(function ($model) {
            return new $this->decorator($model);
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

    /**
     * @param string $regex
     * @param $reducer
     */
    public function qualifier(string $regex, $reducer): void
    {
        $this->reducers[$regex] = $reducer;
    }

    /**
     * @param Search $search
     * @param $query
     * @return mixed
     */
    public function reduce(Search $search, $query)
    {
        foreach ($this->reducers as $regex => $reducer) {
            $query = preg_replace_callback($regex, function ($matches) use ($search, $reducer) {
                if ($reducer instanceof \Closure) {
                    $reducer($search, $matches[1]);
                } elseif (is_array($reducer)) {
                    call_user_func_array([$reducer[0], $reducer[1]], [$search, $matches[1]]);
                }
            }, $query);
        }

        return $query;
    }
}
