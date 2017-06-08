<?php

namespace Naph\Searchlight\Model;

interface SearchlightContract
{
    public function getSearchableFields(): array;

    public function getSearchableIndex(): string;

    public function getSearchableTrashedIndex(): string;

    public function getSearchableType(): string;

    public function getSearchableBody(): array;

    public function getSearchableId(): int;
}
