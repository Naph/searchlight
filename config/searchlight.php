<?php

return [

    /**
     * Array of models implementing SearchlightContract as fully
     * qualified class names.
     */
    'repositories' => [],

    'driver' => 'elasticsearch',

    /**
     * Default index name used for all documents. Name can be changed by
     * getSearchableIndex() per model implementing SearchlightContract.
     */
    'index' => 'searchlight',

    /**
     * Drivers
     */
    'drivers' => [
        'elasticsearch' => [
            'class' => Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver::class,

            /**
             * Host configuration. Default is localhost
             */
            'hosts' => [
                [
                    'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
                    'user'   => env('ELASTICSEARCH_USER'),
                    'pass'   => env('ELASTICSEARCH_PASS'),
                    'host'   => env('ELASTICSEARCH_HOST', 'localhost'),
                    'port'   => env('ELASTICSEARCH_PORT', '9200'),
                ]
            ],

            /**
             * Max search results returned from driver.
             */
            'size' => 2000
        ]
    ]
];
