<?php

return [

    /**
     * Default index name used for all documents. Name can be changed by
     * getSearchableIndex() per model implementing SearchlightContract.
     */
    'index' => 'searchlight',

    /**
     * Max search results returned from driver.
     */
    'size' => 2000,

    /**
     * Array of models implementing SearchlightContract as fully
     * qualified class names.
     */
    'repositories' => [],

    /**
     * Driver
     */
    'driver' => [
        'class' => Naph\Searchlight\Drivers\Elasticsearch\Driver::class,
        'config' => [

            /**
             * Host configuration. Default is localhost
             */
            'hosts' => [
                [
                    'scheme' => getenv('ELASTICSEARCH_SCHEME') ?: 'https',
                    'user'   => getenv('ELASTICSEARCH_USER'),
                    'pass'   => getenv('ELASTICSEARCH_PASS'),
                    'host'   => getenv('ELASTICSEARCH_HOST') ?: 'localhost',
                    'port'   => getenv('ELASTICSEARCH_PORT') ?: '9200',
                ]
            ]
        ]
    ]
];
