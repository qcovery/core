<?php
namespace Libraries\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'librariesajax' => 'Libraries\Controller\Factory::getLibrariesAjaxController',
        ],
    ],
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'VuFind\SessionManager' => 'VuFind\Session\ManagerFactory',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'search_backend' => [
                'factories' => [
                    'Solr' => 'Libraries\Search\Factory\SolrDefaultBackendFactory',
                    'Primo' => 'Libraries\Search\Factory\PrimoBackendFactory',
                    'Findex' => 'Libraries\Search\Factory\FindexBackendFactory',
                ],
            ],
            'search_params' => [
                'abstract_factories' => ['Libraries\Search\Params\PluginFactory'],
                'factories' => [
                    'solr' => 'Libraries\Search\Params\Factory::getSolr',
                    'primo' => 'Libraries\Search\Params\Factory::getPrimo',
                    'findex' => 'Libraries\Search\Params\Factory::getFindex',
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

