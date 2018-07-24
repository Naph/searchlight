<?php

namespace Naph\Searchlight;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\Eloquent\Model;
use Naph\Searchlight\Model\SearchlightContract;

class SearchlightObserver
{
    /**
     * @var array
     */
    private $events;

    /**
     * @var BusDispatcher
     */
    private $bus;

    public function __construct(array $events, BusDispatcher $bus)
    {
        $this->events = $events;
        $this->bus = $bus;
    }

    public function saved(Model $model)
    {
        if ($this->hasEvent('saved') && $model instanceof SearchlightContract) {
            $this->bus->dispatch(new Jobs\Index($model));
        }
    }

    public function deleted(Model $model)
    {
        if ($this->hasEvent('deleted') && $model instanceof SearchlightContract) {
            $this->bus->dispatch(new Jobs\Delete($model));
        }
    }

    public function restored(Model $model)
    {
        if ($this->hasEvent('restored') && $model instanceof SearchlightContract) {
            $this->bus->dispatch(new Jobs\Restore($model));
        }
    }

    /**
     * Has event
     * Checks if the event (called method) can be called
     *
     * @param string $name
     *
     * @return bool
     */
    private function hasEvent(string $name)
    {
        return (in_array('*', $this->events) || in_array($name, $this->events));
    }
}
