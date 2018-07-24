<?php
declare(strict_types=1);

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Naph\Searchlight\Model\Fields;

class ElasticsearchFields extends Fields
{
    /**
     * @param array $fields
     *
     * @return ElasticsearchFields
     * @throws \Naph\Searchlight\Exceptions\SearchlightException
     */
    public static function collect(array $fields): ElasticsearchFields
    {
        return new static($fields);
    }

    /**
     * @return array
     */
    public function getBoostedFields(): array
    {
        $boostedFields = [];

        foreach ($this->fields as $field => $boost) {
            $boostedFields[] = $field.'^'.$boost;
        }

        return $boostedFields;
    }

    /**
     * @param string $query
     * @param bool $prefix
     * @return array
     */
    public function queryString(string $query, $prefix = false)
    {
        $match = [
            'fields' => $this->getBoostedFields(),
            'query' => $query,
            'type' => $prefix ? 'phrase_prefix' : 'most_fields',
            'lenient' => true,
        ];

        return ['multi_match' => $match];
    }

    /**
     * @param array $query
     * @param bool $prefix
     * @return array
     */
    public function queryArray(array $query, $prefix = false)
    {
        $query = implode(' + ', array_map(function ($string) {
            return "\"$string\"";
        }, $query));
        $match = [
            'fields' => $this->getBoostedFields(),
            'query' => $query,
            'analyze_wildcard' => $prefix,
            'lenient' => true
        ];

        return ['simple_query_string' => $match];
    }
}
