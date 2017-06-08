<?php

namespace Naph\Searchlight\Model;

use Illuminate\Support\Facades\Config;
use Naph\Searchlight\Jobs\Delete;
use Naph\Searchlight\Jobs\Index;
use Naph\Searchlight\Jobs\Restore;

trait SearchlightTrait
{
    public function getSearchableIndex(): string
    {
        return Config::get('searchlight.index');
    }

    public function getSearchableTrashedIndex(): string
    {
        return Config::get('searchlight.index').'_trashed';
    }

    public function getSearchableType(): string
    {
        return $this->getTable();
    }

    public function getSearchableBody(): array
    {
        return ['id' => $this->getSearchableId()] + $this->toArray();
    }

    public function getSearchableId(): int
    {
        return (int) $this->id;
    }

    public static function bootSearchlightTrait()
    {
        static::saved(function ($model) {
            dispatch(new Index($model));
        });

        static::deleted(function ($model) {
            dispatch(new Delete($model));
        });

        static::restored(function ($model) {
            dispatch(new Restore($model));
        });
    }
}
