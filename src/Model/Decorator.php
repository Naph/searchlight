<?php

namespace Naph\Searchlight\Model;

use Illuminate\Database\Eloquent\Model;
use Naph\Searchlight\Driver;
use Naph\Searchlight\Exceptions\SearchlightException;

abstract class Decorator implements SearchlightContract
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
     * ModelDecorator constructor.
     *
     * @param Driver $driver
     * @param SearchlightContract $model
     * @internal param array $config
     */
    public function __construct(Driver $driver, SearchlightContract $model)
    {
        $this->driver = $driver;
        $this->model = $model;
    }

    /**
     * Returns normalized searchable fields
     *
     * @return array
     * @throws SearchlightException
     */
    public function getSearchableFields(): array
    {
        $fields = new Fields($this->model->getSearchableFields());

        return $fields->toArray();
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
    public function getSearchableId(): string
    {
        $id = $this->model->getSearchableId();

        if (! $id) {
            throw new SearchlightException('Searchable id is empty.');
        }

        return $id;
    }
}
