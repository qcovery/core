<?php
namespace Libraries\Module\Configuration;

$config = [
//    'controllers' => [
//        'factories' => [
//            'librariesajax' => 'Libraries\Controller\Factory::getLibrariesAjaxController',
//        ],
//    ],
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'Libraries\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            //'VuFind\SessionManager' => 'VuFind\Session\ManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'Libraries\AjaxHandler\PluginManager'
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'search_backend' => [
                'factories' => [
                    'Solr' => 'Libraries\Search\Factory\SolrDefaultBackendFactory',
                    'Primo' => 'Libraries\Search\Factory\PrimoBackendFactory',
                    'Search2' => 'Libraries\Search\Factory\Search2BackendFactory',
                ],
            ],
            'search_params' => [
                'abstract_factories' => ['Libraries\Search\Params\PluginFactory'],
                'factories' => [
                    'solr' => 'Libraries\Search\Params\Factory::getSolr',
                    'primo' => 'Libraries\Search\Params\Factory::getPrimo',
                    'search2' => 'Libraries\Search\Params\Factory::getSearch2',
                ],
            ],
            'recorddriver' => [
                'abstract_factories' => ['VuFind\RecordDriver\PluginFactory'],
                'factories' => [
                    'solrmarc' => 'Libraries\RecordDriver\Factory::getSolrMarc',
                ],
            ],
        ],
    ],
];

return $config;

