<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Naph\Searchlight\Exceptions\SearchlightException;

class Fields
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * Fields constructor.
     *
     * @param array $fields
     * @throws SearchlightException
     */
    public function __construct(array $fields)
    {
        if (! $fields) {
            throw new SearchlightException('Searchable fields are empty.');
        }

        $searchFields = [];

        foreach ($fields as $key => $value) {
            if (is_integer($key)) {
                if (! isset($searchFields[$value])) {
                    $searchFields[$value] = 1.0;
                }
            } else {
                if (! isset($searchFields[$key])) {
                    $searchFields[$key] = floatval($value);
                } elseif ($searchFields[$key] < floatval($value)) {
                    $searchFields[$key] = floatval($value);
                }
            }
        }

        arsort($searchFields);

        $this->fields = $searchFields;
    }

    /**
     * @return int|null|string
     */
    public function first()
    {
        foreach ($this->fields as $field => $boost) {
            return $field;
        }

        return null;
    }
}
