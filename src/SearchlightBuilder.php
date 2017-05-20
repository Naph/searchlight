<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Http\Request;

class SearchlightBuilder
{
    const SYMBOLIC_RANGE_OPERATORS = ['>', '>=', '<=', '<'];

    const LITERAL_RANGE_OPERATORS = ['gt', 'gte', 'lte', 'lt'];

    protected $model;

    public $match = [];

    public $filter = [];

    public $range = [];

    public function __construct(SearchlightContract $model)
    {
        $this->model = $model;
    }

    public function getRawQuery()
    {
        return array_filter([
            'match' => $this->match,
            'filter' => $this->filter,
            'range' => $this->range
        ]);
    }

    public function getModel()
    {
        return $this->model;
    }

    /**
     * Build query from Http Request
     *
     * @param Request $request
     *
     * @return SearchlightBuilder
     */
    public function fromRequest(Request $request): SearchlightBuilder
    {
        if ($request->has('query')) {
            /**
             * Query input expected:
             * query[] = value
             */
            $this->matches($request->get('query'));
        }

        if ($request->has('filter')) {
            /**
             * Filter input expected:
             * filter[term][] = value
             */
            $this->filter($request->get('filter'));
        }

        if ($request->has('range')) {
            /**
             * Range input expected:
             * range[term][>][] = value
             */
            foreach ($request->get('range') as $term => $ranges) {
                foreach ($ranges as $operator => $value) {
                    $this->range([$term, $operator, $value]);
                }
            }
        }

        return $this;
    }

    /**
     * Search by matching multiple fields with one query string
     *
     * @param string|array      $query
     * @param string|array|null $fields
     *
     * @return SearchlightBuilder
     */
    public function matches($query, $fields = null): SearchlightBuilder
    {
        if (! $query) {
            return $this;
        }

        $this->match[] = compact('query', 'fields');

        return $this;
    }

    /**
     * Filter terms
     * Search filters that must be set
     *
     *  Filter array example:
     *  $array = [
     *    'term' => 'query',
     *    'term' => null,
     *    ...
     *  ];
     *
     * @param array $array
     *
     * @return SearchlightBuilder
     */
    public function filter(array $array): SearchlightBuilder
    {
        $this->filter = array_merge($this->filter, $array);

        return $this;
    }

    /**
     * Filter by range
     *
     * Range array example:
     * $array = [
     *   ['term', '>', 'number'],
     *   ['term', '<', 'number'],
     *   ...
     * ];
     *
     * or as single:
     * $array = ['term', '>=', 'number'];
     *
     * @param array $array
     *
     * @throws \UnexpectedValueException using wrong operator
     * @return SearchlightBuilder
     */
    public function range(array $array): SearchlightBuilder
    {
        $range = is_array(reset($array)) ? $array : [$array];

        foreach ($range as $key => $array) {
            if (! ($array[2] ?? false)) {
                unset($range[$key]);
            }
        }

        $this->range = array_merge($this->range, $range);

        return $this;
    }

    public function normaliseRangeOperator(string $operator, $scheme = self::LITERAL_RANGE_OPERATORS)
    {
        if ($index = array_search($operator, self::LITERAL_RANGE_OPERATORS) !== -1) {
            return $scheme[$index];
        }

        if ($index = array_search($operator, self::SYMBOLIC_RANGE_OPERATORS) !== -1) {
            return $scheme[$index];
        }

        throw new \UnexpectedValueException('Range operator is not recognised: "'.$operator.'"');
    }

    public function isEmpty(): bool
    {
        return ! ($this->match || array_filter($this->filter) || $this->range);
    }
}
