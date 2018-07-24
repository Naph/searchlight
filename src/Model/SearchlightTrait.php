<?php

namespace Naph\Searchlight\Model;

use Illuminate\Support\Str;

trait SearchlightTrait
{
    /**
     * @return string
     */
    public function getSearchableIndex(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getSearchableType(): string
    {
        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }
}
