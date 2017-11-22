<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Searchlight Search Driver
    |--------------------------------------------------------------------------
    |
    | Here you may choose one of the search drivers below to use as
    | the logic used by the Search builder. Currently the fallback is
    | the elasticsearch driver as it's the only one supporting multi-model
    | search currently.
    |
    */

    'driver' => getenv('SEARCHLIGHT_DRIVER') ?: 'elasticsearch',

    /*
    |--------------------------------------------------------------------------
    | Search Result Size
    |--------------------------------------------------------------------------
    |
    | This number represents the default amount of results returned from both
    | the get and completion methods from the Search builder. Raising this
    | number can result in a slower search. Use this as a fallback when not
    | explicitly tuning result size for pagination.
    |
    */

    'size' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Fully-qualified Repository Classes
    |--------------------------------------------------------------------------
    |
    | Here you can populate the models implementing the SearchlightContract
    | you wish to index. Models listed here will trigger jobs when created,
    | saved, deleted and restored in order to keep the index up to date.
    | NOTE: Not all drivers support indexing.
    |
    */

    'repositories' => [],

    /*
    |--------------------------------------------------------------------------
    | Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Here are each of the drivers configurations which are natively
    | supported by the Searchlight package. All mission critical values have
    | been conveniently sourced from environment variables.
    |
    */

    'drivers' => [

        'elasticsearch' => [
            'index' => getenv('ELASTICSEARCH_INDEX') ?: 'searchlight',
            'hosts' => [
                [
                    'scheme' => getenv('ELASTICSEARCH_SCHEME') ?: 'http',
                    'user'   => getenv('ELASTICSEARCH_USER'),
                    'pass'   => getenv('ELASTICSEARCH_PASS'),
                    'host'   => getenv('ELASTICSEARCH_HOST') ?: 'localhost',
                    'port'   => getenv('ELASTICSEARCH_PORT') ?: '9200',
                ]
            ],
        ],

        'eloquent' => [

        ],

    ]
];
