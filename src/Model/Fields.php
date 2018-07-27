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

        foreach ($fields as $key => $value) {
            if (is_integer($key)) {
                $this->setField($value, 1.0);
            } elseif (is_float($value) || is_integer($value)) {
                $this->setField($key, floatval($value));
            } elseif (is_array($value)) {
                $this->setField($key, array_get($value, 'value', 1.0), array_get($value, 'type'));
            }
        }

        uasort($this->fields, function ($a, $b) {
            return $a['value'] < $b['value'] ? 1: -1;
        });
    }

    public function collect()
    {
        return collect($this->fields);
    }

    /**
     * @param string $key
     * @param float $value
     * @param null|string $type
     */
    public function setField(string $key, float $value, ?string $type = null)
    {
        $this->fields[$key] = compact('value', 'type');
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
