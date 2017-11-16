<?php

namespace Naph\Searchlight\Model;

interface SearchlightContract
{
    /**
     * Searchable properties
     *
     * @return array
     */
    public function getSearchableFields(): array;

    /**
     * Custom index in which this model is stored
     *
     * @return string
     */
    public function getSearchableIndex(): string;

    /**
     * Type or table name
     *
     * @return string
     */
    public function getSearchableType(): string;

    /**
     * Document id
     *
     * @return string
     */
    public function getSearchableId(): string;
}
