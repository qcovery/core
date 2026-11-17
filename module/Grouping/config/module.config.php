<?php

namespace Grouping\Module\Configuration;

/*
 * Requires the RecordDriver module to be loaded before this module
 * (Grouping\RecordDriver\SolrMarc extends RecordDriver\RecordDriver\SolrMarc).
 *
 * Grouping is switched on per instance in config.ini:
 *
 * [Grouping]
 * enabled = true
 * ; Solr field used for grouping (default: matchkey_str)
 * field = matchkey_str
 * ; Max. number of records per group (default: 100)
 * ;limit = 100
 * ; Optional filter for the duplicates tab (collection_details value)
 * ;collection_details = ...
 */
$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'Grouping\RecordDriver\SolrMarc' => 'RecordDriver\RecordDriver\SolrDefaultFactory',
                ],
                'aliases' => [
                    'VuFind\RecordDriver\SolrMarc' => 'Grouping\RecordDriver\SolrMarc',
                ],
                'delegators' => [
                    'Grouping\RecordDriver\SolrMarc' => [
                        'VuFind\RecordDriver\IlsAwareDelegatorFactory',
                    ],
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'Grouping\RecordTab\Duplicates' => 'Grouping\RecordTab\DuplicatesFactory',
                ],
                'aliases' => [
                    'Duplicates' => 'Grouping\RecordTab\Duplicates',
                    'duplicates' => 'Grouping\RecordTab\Duplicates',
                ],
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'Grouping\Search\Factory\SolrDefaultBackendFactory',
                ],
            ],
        ],
    ],
];

return $config;
