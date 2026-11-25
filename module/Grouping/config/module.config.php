<?php

namespace Grouping\Module\Configuration;

/*
 * Requires the RecordDriver and Libraries modules to be loaded before this module
 * (Grouping\RecordDriver\SolrMarc extends RecordDriver\RecordDriver\SolrMarc,
 * Grouping\Search\Factory\SolrDefaultBackendFactory extends the Libraries
 * backend factory to keep the library filter of the Libraries connector).
 *
 * The "Solr" backend factory registered below replaces the default factories
 * of backend registries for Libraries and RelevancePicker. The
 * RelevancePicker explain data is therefore attached by the Grouping
 * RecordCollectionFactory itself.
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
