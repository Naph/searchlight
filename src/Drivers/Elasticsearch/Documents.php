<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Illuminate\Support\Collection;

/**
 * Trait HasModelsProperty
 *
 * @property Document[] $models
 */
trait Documents
{
    /**
     * @return bool
     */
    protected function supportsMultisearch()
    {
        return $this->documents()->count() > 1;
    }

    /**
     * @param bool $withTrashed
     *
     * @return string[]
     */
    protected function searchableIndices($withTrashed = false): array
    {
        $indices = $this->documents()->map->searchableIndex;

        if ($withTrashed) {
            return $indices->merge($this->documents()->map->trashedIndex);
        }

        return $indices->toArray();
    }

    /**
     * @return string[]
     */
    public function searchableTypes(): array
    {
        return $this->documents()->map->searchableType->toArray();
    }

    /**
     * @return string[]
     */
    public function searchableFields(): array
    {
        return $this->documents()->flatMap->boostedFields()->toArray();
    }

    /**
     * Alias for property
     *
     * @return Collection|Document[]
     */
    public function documents(): Collection
    {
        return collect($this->models ?? []);
    }
}
