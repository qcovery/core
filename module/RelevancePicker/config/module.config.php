<?php
namespace RelevancePicker\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
//            'Libraries\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'RelevancePicker\Search\BackendManager' => 'RelevancePicker\Search\BackendManagerFactory',
            'RelevancePicker\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'RelevancePicker\Search\Results\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
//            'VuFind\AjaxHandler\PluginManager' => 'Libraries\AjaxHandler\PluginManager',
            'VuFind\Search\BackendManager' => 'RelevancePicker\Search\BackendManager',
            'VuFind\Search\Params\PluginManager' => 'RelevancePicker\Search\Params\PluginManager',
            'VuFind\Search\Results\PluginManager' => 'RelevancePicker\Search\Results\PluginManager',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'search_params' => [
                'factories' => [
                    'RelevancePicker\Search\Solr\Params' => 'RelevancePicker\Search\Solr\ParamsFactory',
                    'RelevancePicker\Search\Search2\Params' => 'RelevancePicker\Search\Search2\ParamsFactory',
                ],
                'aliases' => [
                    'solr' => 'RelevancePicker\Search\Solr\Params',
                    'search2' => 'RelevancePicker\Search\Search2\Params',
                ],
            ],
            'search_results' => [
                'factories' => [
                    'RelevancePicker\Search\Solr\Results' => 'RelevancePicker\Search\Solr\ResultsFactory',
                    'RelevancePicker\Search\Search2\Results' => 'RelevancePicker\Search\Search2\ResultsFactory',
                ],
                'aliases' => [
                    'solr' => 'RelevancePicker\Search\Solr\Results',
                    'search2' => 'RelevancePicker\Search\Search2\Results',
                ],
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'RelevancePicker\Search\Factory\SolrDefaultBackendFactory',
                    'Search2' => 'RelevancePicker\Search\Factory\Search2BackendFactory',
                ],
            ],
        ],
    ],
];

return $config;
