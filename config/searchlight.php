<?php

return [

    /**
     * Array of models implementing SearchlightContract as fully
     * qualified class names.
     */
    'repositories' => [],

    'driver' => 'elasticsearch',

    /**
     * Drivers
     */
    'drivers' => [
        'elasticsearch' => [
            'class' => Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver::class,

            /**
             * Default index name used for all documents. Name can be changed by
             * getSearchableIndex() per model implementing SearchlightContract.
             */
            'index' => 'searchlight',

            /**
             * Host configuration. Default is localhost
             */
            'hosts' => [
                [
                    'scheme' => getenv('ELASTICSEARCH_SCHEME') ?? 'http',
                    'user'   => getenv('ELASTICSEARCH_USER'),
                    'pass'   => getenv('ELASTICSEARCH_PASS'),
                    'host'   => getenv('ELASTICSEARCH_HOST') ?? 'localhost',
                    'port'   => getenv('ELASTICSEARCH_PORT') ?? '9200',
                ]
            ],

            /**
             * Max search results returned from driver.
             */
            'size' => 2000,
        ],

        'eloquent' => [
            'class' => Naph\Searchlight\Drivers\Eloquent\EloquentDriver::class,

            /**
             * Max search results returned from driver.
             */
            'size' => 2000,
        ],
    ]
];
