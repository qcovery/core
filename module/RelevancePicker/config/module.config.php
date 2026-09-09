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
            'search_params' => [
                'factories' => [
                    'RelevancePicker\Search\Solr\Params' => 'RelevancePicker\Search\Solr\ParamsFactory',
                ],
                'aliases' => [
                    'solr' => 'RelevancePicker\Search\Solr\Params',
                    'VuFind\Search\Solr\Params' => 'RelevancePicker\Search\Solr\Params',
                ],
            ],
            'search_results' => [
                'factories' => [
                    'RelevancePicker\Search\Solr\Results' => 'RelevancePicker\Search\Solr\ResultsFactory',
                ],
                'aliases' => [
                    'solr' => 'RelevancePicker\Search\Solr\Results',
                ],
            ],
        ],
    ],
];

return $config;
