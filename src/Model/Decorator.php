<?php

namespace Naph\Searchlight\Model;

use Illuminate\Database\Eloquent\{
    Builder, Model
};
use Naph\Searchlight\Driver;
use Naph\Searchlight\Exceptions\SearchlightException;

class Decorator implements SearchlightContract
{
    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var Model|SearchlightContract
     */
    protected $model;

    /**
     * @var \Naph\Searchlight\Model\Fields
     */
    protected $fields;

    /**
     * ModelDecorator constructor.
     *
     * @param Driver $driver
     * @param SearchlightContract $model
     *
     * @throws \Naph\Searchlight\Exceptions\SearchlightException
     */
    public function __construct(Driver $driver, SearchlightContract $model)
    {
        $this->driver = $driver;
        $this->model = $model;
        $this->fields = new Fields($model->getSearchableFields());
    }

    /**
     * Returns normalized searchable fields
     *
     * @return array
     * @throws SearchlightException
     */
    public function getSearchableFields(): array
    {
        return $this->fields->toArray();
    }

    /**
     * Get index from model
     *
     * @return string
     */
    public function getSearchableIndex(): string
    {
        return $this->model->getSearchableIndex() ?: $this->driver->config('index');
    }

    /**
     * Get type of model
     *
     * @return string
     */
    public function getSearchableType(): string
    {
        return $this->model->getSearchableType() ?: get_class($this->model);
    }

    /**
     * Get id of model
     *
     * @return string
     * @throws SearchlightException
     */
    public function getPrimaryKey(): string
    {
        $id = $this->model->getKey();

        if (! $id) {
            throw new SearchlightException('Searchable id is empty.');
        }

        return $id;
    }

    /**
     * Get id name of model
     *
     * @return string
     */
    public function getPrimaryKeyName()
    {
        return $this->model->getKeyName();
    }

    /**
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Bridge model methods
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->model, $name)) {
            return call_user_func_array([$this->model, $name], $arguments);
        }
    }

    /**
     * Bridge dynamic model attributes
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->model->getAttribute($key);
    }
}
