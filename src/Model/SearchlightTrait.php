<?php

namespace Naph\Searchlight\Model;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\App;
use Naph\Searchlight\Jobs\Delete;
use Naph\Searchlight\Jobs\Index;
use Naph\Searchlight\Jobs\Restore;

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
        return $this->getTable();
    }

    /**
     * @return array
     */
    public function getSearchableBody(): array
    {
        return ['id' => $this->getSearchableId()] + array_map(function ($attribute) {
            return is_bool($attribute) ? intval($attribute) : $attribute;
        }, $this->toArray());
    }

    /**
     * @return int
     */
    public function getSearchableId(): int
    {
        return (int) $this->id;
    }

    /**
     * Boot SearchlightTrait to bind model events
     */
    public static function bootSearchlightTrait()
    {
        $dispatcher = App::make(Dispatcher::class);

        static::saved(function ($model) use ($dispatcher) {
            $dispatcher->dispatch(new Index($model));
        });

        static::deleted(function ($model) use ($dispatcher) {
            $dispatcher->dispatch(new Delete($model));
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) use ($dispatcher) {
                $dispatcher->dispatch(new Restore($model));
            });
        }
    }
}
