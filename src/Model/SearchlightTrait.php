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
     * @return string
     */
    public function getSearchableId(): string
    {
        return (string) $this->id;
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
