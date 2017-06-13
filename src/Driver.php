<?php

namespace Naph\Searchlight;

use Naph\Searchlight\Model\SearchlightContract;

abstract class Driver
{
    protected $config;

    protected $reducers;

    protected $repositories;

    public function __construct(array $repositories, array $config) {
        $this->config = $config;
        $this->reducers = [];
        $this->repositories = $repositories;
    }

    public function getConfig($key)
    {
        return $this->config[$key];
    }

    public function getRepositories(): array
    {
        return $this->repositories;
    }

    abstract public function index(SearchlightContract $model);

    abstract public function delete(SearchlightContract $model);

    abstract public function restore(SearchlightContract $model);

    abstract public function deleteAll();

    abstract public function builder(): Builder;

    public function qualifier(string $regex, $reducer)
    {
        $this->reducers[$regex] = $reducer;
    }

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
