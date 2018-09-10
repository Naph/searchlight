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
     * @var Fields
     */
    protected $fields;

    /**
     * ModelDecorator constructor.
     *
     * @param Driver $driver
     * @param SearchlightContract $model
     *
     * @throws SearchlightException
     */
    public function __construct(Driver $driver, SearchlightContract $model)
    {
        $this->driver = $driver;
        $this->model = $model;
        try {
            $this->fields = new Fields($model->getSearchableFields());
        } catch (SearchlightException $e) {
            throw new SearchlightException(sprintf('(%s): %s', get_class($this->models[0]), $e->getMessage()));
        }
    }

    public function getModelClass(): string
    {
        return get_class($this->model);
    }

    /**
     * Returns normalized searchable fields
     *
     * @return array
     */
    public function getSearchableFields(): array
    {
        return $this->fields->toArray();
    }

    /**
     * Get index for the type
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

        if (!$id) {
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
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->model, $name)) {
            return call_user_func_array([$this->model, $name], $arguments);
        }

        throw new \BadMethodCallException();
    }

    /**
     * Bridge dynamic model attributes
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        switch ($key) {
            case 'searchableIndex':
                return $this->getSearchableIndex();
            case 'searchableFields':
                return $this->getSearchableFields();
            case 'searchableType':
                return $this->getSearchableType();
        }

        return $this->model->getAttribute($key);
    }
}
