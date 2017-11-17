<?php

namespace Naph\Searchlight\Model;

trait SearchlightTrait
{
    /**
     * @return string
     */
    public function getSearchableIndex(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getSearchableType(): string
    {
        return $this->getTable();
    }

    /**
     * @return string
     */
    public function getSearchableId(): string
    {
        return (string) $this->id;
    }
}
