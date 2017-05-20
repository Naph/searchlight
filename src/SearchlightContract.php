<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface SearchableContract
 * @package Velg\Searchable
 */
interface SearchlightContract
{
    /**
     * Searchable Fields
     * Array of model property keys associating boost values.
     *
     * Field array example:
     * [
     *     'email'      => 3,
     *     'first_name' => 2,
     *     'last_name'  => 1,
     *     'life_story' => 0.1
     * ]
     *
     * @return array
     */
    public function getSearchableFields(): array;

    public function getSearchableIndex(): string;

    public function getSearchableType(): string;

    public function getSearchableBody(): array;

    public function getSearchableId(): int;

    public function indexValidation(): array;

    public function indexValidates(): bool;

    public function search($query): Builder;
}
