<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface SearchlightDriver
{
    public static function create(array $config): SearchlightDriver;

    public function index(SearchlightContract $model);

    public function delete(SearchlightContract $model);

    public function deleteAll(string $index = '');

    public function buildQuery(SearchlightBuilder $builder);

    public function multi($models, $query): Collection;

    public function search(SearchlightContract $model, $query): Builder;
}
