<?php
declare(strict_types=1);

namespace Naph\Searchlight;

interface SearchlightQuery
{
    public static function create(SearchlightBuilder $model): SearchlightQuery;

    public function build();
}
