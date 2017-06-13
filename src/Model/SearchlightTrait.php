<?php

namespace Naph\Searchlight\Model;

use Illuminate\Contracts\Bus\Dispatcher;
use Naph\Searchlight\Jobs\Delete;
use Naph\Searchlight\Jobs\Index;
use Naph\Searchlight\Jobs\Restore;

trait SearchlightTrait
{
    public function getSearchableIndex(): string
    {
        return '';
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
            self::$dispatcher->dispatch(new Index($model));
        });

        static::deleted(function ($model) {
            self::$dispatcher->dispatch(new Delete($model));
        });

        static::restored(function ($model) {
            self::$dispatcher->dispatch(new Restore($model));
        });
    }
}
