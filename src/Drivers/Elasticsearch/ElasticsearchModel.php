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
     * @return array
     */
    public function metadata(bool $trashed = false): array
    {
        return [
            'index' => $trashed
                ? $this->getTrashedIndex()
                : $this->getSearchableIndex(),
            'type' => $this->getSearchableType(),
            'id' => $this->getSearchableId(),
        ];
    }

    /**
     * @return array
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
     */
    public function query(): array
    {
        return array_merge($this->metadata(), ['body' => $this->body()]);
    }
}
