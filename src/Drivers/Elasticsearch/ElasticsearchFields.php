<?php
declare(strict_types=1);

namespace Naph\Searchlight\Drivers\Elasticsearch;

use Naph\Searchlight\Fields;

class ElasticsearchFields extends Fields
{
    public static function collect(array $fields): ElasticsearchFields
    {
        return new static($fields);
    }

    public function getBoostedFields(): array
    {
        $boostedFields = [];

        foreach ($this->fields as $field => $boost) {
            $boostedFields[] = $field.'^'.$boost;
        }

        return $boostedFields;
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
