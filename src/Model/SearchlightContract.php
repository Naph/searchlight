<?php

namespace Naph\Searchlight\Model;

interface SearchlightContract
{
    /**
     * Fields which are searched (by default)
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
     * Document body
     *
     * @return array
     */
    public function getSearchableBody(): array;

    /**
     * Document id
     *
     * @return int
     */
    public function getSearchableId(): int;
}
