<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Naph\Searchlight\Model\Decorator;

class ElasticsearchModel extends Decorator
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
     * @throws \Naph\Searchlight\Exceptions\SearchlightException
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
     * @throws \Naph\Searchlight\Exceptions\SearchlightException
     */
    public function body(): array
    {
        $body = [];

        foreach ($this->getSearchableFields() as $field => $boost) {
            $body[$field] = $this->model->getAttributeValue($field);
        }

        return $body;
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
     * Combine metadata with body
     *
     * @return array
     * @throws \Naph\Searchlight\Exceptions\SearchlightException
     */
    public function query(): array
    {
        return array_merge($this->metadata(), ['body' => $this->body()]);
    }
}
