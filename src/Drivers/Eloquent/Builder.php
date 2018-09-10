<?php

namespace Naph\Searchlight\Drivers\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Naph\Searchlight\Builder as SearchlightBuilder;
use Naph\Searchlight\Model\Decorator;

class Builder extends SearchlightBuilder
{
    /**
     * @return Decorator
     */
    public function getSingleModel(): Decorator
    {
        return $this->models[0];
    }

    /**
     * Fresh builder instance
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function build(): \Illuminate\Database\Eloquent\Builder
    {
        $model = $this->getSingleModel();
        $query = $model->newQuery();

        // Range
        foreach ($this->range as $range) {
            $query->where($range[0], $range[1], $range[2]);
        }

        // Filter
        foreach ($this->filter as $field => $value) {
            $query->where($field, $value);
        }

        // Match
        $query->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($model) {
            foreach ($this->match as $match) {
                $fields = $match['fields'];

                if (is_string($fields)) {
                    $fields = [$fields];
                }

                if ($fields === null) {
                    $fields = array_keys($model->getSearchableFields());
                }

                foreach ($fields as $field) {
                    // TODO: Complex query
                    $query->orWhere($field, 'LIKE', "%{$match['query']}%");
                }
            }
        });

        return $query->take($this->size);
    }

    /**
     * Get builder results
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->build()->get();
    }

    /**
     * Search-as-you-type enhanced get
     *
     * @return Collection
     */
    public function completion(): Collection
    {
        return $this->build()->get();
    }

    /**
     * Get paginated builder results
     *
     * @param int $perPage
     * @param int $page
     *
     * @return LengthAwarePaginator
     */
    public function paginate($perPage, $page): LengthAwarePaginator
    {
        return $this->build()->paginate($perPage);
    }
}
