<?php
declare(strict_types=1);

namespace Naph\Searchlight;

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
     * @param $key
     * @return mixed
     */
    public function getConfig($key)
    {
        return $this->config[$key];
    }

    /**
     * @return array
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }

    /**
     * @param SearchlightContract $model
     * @return mixed
     */
    abstract public function index(SearchlightContract $model);

    /**
     * @param SearchlightContract $model
     * @return mixed
     */
    abstract public function delete(SearchlightContract $model);

    /**
     * @param SearchlightContract $model
     * @return mixed
     */
    abstract public function restore(SearchlightContract $model);

    /**
     * @return mixed
     */
    abstract public function deleteAll();

    /**
     * @return Builder
     */
    abstract public function builder(): Builder;

    /**
     * @param string $regex
     * @param $reducer
     */
    public function qualifier(string $regex, $reducer)
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
