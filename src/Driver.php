<?php

namespace Naph\Searchlight;

use Naph\Searchlight\Model\SearchlightContract;

abstract class Driver
{
    protected $reducers = [];

    abstract public function __construct(array $config);

    abstract public function index(SearchlightContract $model);

    abstract public function delete(SearchlightContract $model);

    abstract public function deleteAll(string $index = '');

    abstract public function builder(): Builder;

    public function qualifier(string $regex, $reducer)
    {
        $this->reducers[$regex] = $reducer;
    }

    public function reduce(Search $search, $query)
    {
        foreach ($this->reducers as $regex => $reducer) {
            $query = preg_replace_callback($regex, function ($matches) use ($search, $reducer) {
                if ($reducer instanceof \Closure) {
                    $reducer($search, $matches[1]);
                } elseif (is_array($reducer)) {
                    call_user_func_array([$reducer[0], $reducer[1]], [$search, $matches[1]]);
                }
            }, $query);
        }

        return $query;
    }
}
