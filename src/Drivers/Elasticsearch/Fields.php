<?php
declare(strict_types=1);

namespace Velg\Searchable\Fields;

class Fields
{
    protected $fields;

    public function __construct(array $fields)
    {
        if (! $fields) {
            throw new \Exception('No fields present.');
        }

        $searchableFields = [];

        foreach ($fields as $key => $value) {
            is_integer($key)
                ? $searchableFields[] = ['field' => $value, 'boost' => 1.0]
                : $searchableFields[] = ['field' => $key, 'boost' => floatval($value)];
        }

        usort($searchableFields, function($a, $b) {
            return $a['boost'] <=> $b['boost'];
        });

        $this->fields = $searchableFields;
    }

    public static function collect(array $fields): Fields
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
