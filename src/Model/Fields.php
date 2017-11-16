<?php
declare(strict_types=1);

namespace Naph\Searchlight\Model;

use Illuminate\Contracts\Support\Arrayable;
use Naph\Searchlight\Exceptions\SearchlightException;

class Fields implements Arrayable
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
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->fields;
    }
}
