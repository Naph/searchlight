<?php
declare(strict_types=1);

namespace Naph\Searchlight;

use Illuminate\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Naph\Searchlight\Jobs\{Delete, Index};

/**
 * Trait SearchableTrait
 * Intended to extend Eloquent Models
 *
 * @property int id
 * @property bool exists
 * @method string getTable()
 * @method array toArray()
 */
trait SearchlightTrait
{
    protected $driver;

    public function getSearchableIndex(): string
    {
        return App::make(SearchlightDriver::class)->getIndex();
    }

    public function getSearchableType(): string
    {
        return $this->getTable();
    }

    public function getSearchableId(): int
    {
        if ($this->exists) {
            return $this->id;
        }

        return 0;
    }

    public function getSearchableBody(): array
    {
        return ['id' => $this->getSearchableId()] + $this->toArray();
    }

    public static function searchQuery()
    {
        return new SearchlightBuilder(new static());
    }

    /**
     * Validation for indices
     *
     * @see https://laravel.com/docs/master/validation
     * @return array
     */
    public function indexValidation(): array
    {
        return [];
    }

    /**
     * Returns validity of index
     *
     * @return bool
     */
    public function indexValidates(): bool
    {
        if (! $this->indexValidation()) {
            return true;
        }

        $validator = Validator::make($this->getAttributes(), $this->indexValidation());

        return ! $validator->fails();
    }

    /**
     * @param SearchlightBuilder|Request|string $query
     * @return Builder
     */
    public function search($query): Builder
    {
        if (! $query) {
            return $this->query();
        }

        $driver = App::make(SearchlightDriver::class);

        if ($query instanceof SearchlightBuilder) {
            if ($query->isEmpty()) {
                return $this->query();
            }

            $query = $driver->buildQuery($query);
        } elseif ($query instanceof Request) {
            $query = $driver->buildQuery(self::searchQuery()->fromRequest($query));
        } else {
            $query = $driver->buildQuery(self::searchQuery()->matches($query));
        }

        return $driver->search($this, $query);
    }

    public static function bootSearchableTrait(Dispatcher $dispatcher)
    {
        static::saved(function (SearchlightContract $model) use ($dispatcher) {
            $dispatcher->dispatch(new Index($model));
        });

        static::deleted(function (SearchlightContract $model) use ($dispatcher) {
            $dispatcher->dispatch(new Delete($model));
        });
    }
}
