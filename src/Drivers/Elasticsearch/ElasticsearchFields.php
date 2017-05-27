<?php

namespace Naph\Searchlight\Drivers\Elasticsearch;

class ElasticsearchFields
{
    protected $fields;

    public function __construct(array $fields)
    {
        if (! $fields) {
            throw new \Exception('No fields present.');
        }

        $searchFields = [];

        foreach ($fields as $key => $value) {
            is_integer($key)
                ? $searchFields[] = ['field' => $value, 'boost' => 1.0]
                : $searchFields[] = ['field' => $key, 'boost' => floatval($value)];
        }

        usort($searchFields, function($a, $b) {
            return $a['boost'] <=> $b['boost'];
        });

        $this->fields = $searchFields;
    }

    public static function collect(array $fields): ElasticsearchFields
    {
        return new static($fields);
    }

    public function getBoostedFields(): array
    {
        return array_map(function ($field) {
            return $field['field'].'^'.$field['boost'];
        }, $this->fields);
    }

    public function queryString(string $query)
    {
        $match = [
            'fields' => $this->getBoostedFields(),
            'query' => $query,
            'type' => 'most_fields',
            'lenient' => true
        ];

        return ['multi_match' => $match];
    }

    public function queryArray(array $query, $glue = ' + ')
    {
        $query = implode($glue, array_map(function ($string) {
            return "\"$string\"";
        }, $query));
        $match = [
            'fields' => $this->getBoostedFields(),
            'query' => $query,
            'lenient' => true
        ];

        return ['simple_query_string' => $match];
    }
}
