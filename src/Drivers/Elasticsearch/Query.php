<?php
declare(strict_types=1);

namespace Naph\Searchlight\Elasticsearch;

use Naph\Searchlight\SearchlightQuery;
use Naph\Searchlight\SearchlightBuilder;
use Velg\Searchable\Fields\Fields;

class Query implements SearchlightQuery
{
    protected $queryBuilder;

    public function __construct(SearchlightBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public static function create(SearchlightBuilder $queryBuilder): SearchlightQuery
    {
        return new static($queryBuilder);
    }

    public function build()
    {
        $vars = $this->queryBuilder->getRawQuery();
        $search = [];

        foreach ($vars as $property => $values) {
            $search = array_merge_recursive($search, $this->$property($values));
        }

        return ['query' => ['bool' => $search]];
    }

    public function match(array $values): array
    {
        $must = [];
        foreach ($values as $matchQuery) {
            $fields = [];
            if (is_null($matchQuery['fields'])) {
                $fields = Fields::collect($this->queryBuilder->getModel()->getSearchableFields());
            } elseif (is_array($matchQuery['fields'])) {
                $fields = Fields::collect($matchQuery['fields']);
            } elseif (is_string($matchQuery['fields'])) {
                $fields = Fields::collect([$matchQuery['fields']]);
            }

            if (is_string($matchQuery['query'])) {
                $must[] = $fields->queryString($matchQuery['query']);
            } elseif (is_array($matchQuery['query'])) {
                $must[] = $fields->queryArray($matchQuery['query']);
            }
        }

        return compact('must');
    }

    public function filter(array $filters): array
    {
        $must_not = [];
        $must = [];
        foreach ($filters as $term => $query) {
            if (is_null($query)) {
                $must_not[] = [
                    'exists' => ['field' => $term]
                ];
            } elseif ($query) {
                $must[] = [
                    (is_array($query) ? 'terms' : 'term') => [$term => $query]
                ];
            }
        }

        return array_filter(compact('must_not', 'must'));
    }

    public function range(array $ranges): array
    {
        $must = [];
        foreach ($ranges as $range) {
            $operator = $this->queryBuilder->normaliseRangeOperator($range[1]);
            $must[] = [
                'range' => [$range[0] => [$operator => $range[2]]]
            ];
        }

        return compact('must');
    }
}
