<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Naph\Searchlight\Exceptions\SearchlightException;
use Naph\Searchlight\Model\Decorator;

class Document extends Decorator
{
    /**
     * Get soft deleted index
     *
     * @return string
     */
    public function getTrashedIndex(): string
    {
        return $this->getSearchableIndex() . '_trashed';
    }

    /**
     * API consumable model metadata
     *
     * @param bool $trashed
     *
     * @return array
     * @throws SearchlightException
     */
    public function metadata(bool $trashed = false): array
    {
        return [
            '_index' => $trashed
                ? $this->getTrashedIndex()
                : $this->getSearchableIndex(),
            '_type' => $this->getSearchableType(),
            '_id' => $this->getPrimaryKey(),
        ];
    }

    /**
     * @return array
     */
    public function body(): array
    {
        $body = [];

        foreach ($this->getSearchableFields() as $name => $field) {
            $body[$name] = $this->model->getAttributeValue($name);
        }

        return $body;
    }

    /**
     * @return array
     */
    public function boostedFields()
    {
        $fields = [];

        foreach ($this->getSearchableFields() as $name => $field) {
            $fields[] = $name . '^' . $field['boost'];
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function mapping(): array
    {
        $properties = [];

        foreach ($this->getSearchableFields() as $name => $field) {
            if (isset($field['type'])) {
                $properties[$name] = [
                    'type' => $field['type'],
                ];
            }
        }

        if (empty($properties)) {
          return [];
        }

        return [$this->getSearchableType() => compact('properties')];
    }

    /**
     * If model soft deletes
     *
     * @return bool
     */
    public function softDeletes(): bool
    {
        return method_exists($this->model, 'trashed');
    }

    /**
     * If model is soft deleted
     *
     * @return bool
     */
    public function isSoftDeleted(): bool
    {
        return method_exists($this->model, 'trashed') && $this->model->trashed();
    }

    /**
     * @param string $key
     *
     * @return mixed|string
     */
    public function __get($key)
    {
        if ($key === 'trashedIndex') {
            return $this->getTrashedIndex();
        }

        return parent::__get($key);
    }
}
