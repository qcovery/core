<?php
namespace RelevancePicker\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'RelevancePicker\Search\BackendManager' => 'RelevancePicker\Search\BackendManagerFactory',
        ],
        'aliases' => [
            'VuFind\Search\BackendManager' => 'RelevancePicker\Search\BackendManager',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
/*
            'search_backend' => [
                'delegators' => [
                    'Solr' => ['RelevancePicker\Search\Factory\SolrDefaultBackendDelegatorFactory'],
                ]
            ],
*/
            'search_params' => [
                'factories' => [
                    'RelevancePicker\Search\Search2\Params' => 'RelevancePicker\Search\Search2\ParamsFactory',
                    'RelevancePicker\Search\Solr\Params' => 'RelevancePicker\Search\Solr\ParamsFactory',
                ],
                'aliases' => [
                    'search2' => 'RelevancePicker\Search\Search2\Params',
                    'solr' => 'RelevancePicker\Search\Solr\Params',
                ]
            ],
            'search_results' => [
                'factories' => [
                    'RelevancePicker\Search\Search2\Results' => 'RelevancePicker\Search\Search2\ResultsFactory',
                    'RelevancePicker\Search\Solr\Results' => 'RelevancePicker\Search\Solr\ResultsFactory',
                ],
                'aliases' => [
                    'search2' => 'RelevancePicker\Search\Search2\Results',
                    'solr' => 'RelevancePicker\Search\Solr\Results',
                ]
            ],
        ],
    ],
];

return $config;

