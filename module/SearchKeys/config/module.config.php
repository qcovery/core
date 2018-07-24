<?php
namespace SearchKeys\Module\Configuration;

$config = [
/*
    'vufind' => [
        'plugin_managers' => [
            'search_params' => [
                'abstract_factories' => ['SearchKeys\Search\Params\PluginFactory'],
                'factories' => [
                    'search2' => 'SearchKeys\Search\Params\Factory::getSearch2',
                    'solr' => 'SearchKeys\Search\Params\Factory::getSolr',
                    'primo' => 'SearchKeys\Search\Params\Factory::getPrimo',
                ],
            ],
        ],
    ],
*/
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'SearchKeys\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
//            'SearchKeys\Search\SearchKeysHelper' => 'SearchKeys\Search\SearchKeysHelperFactory',
        ],
        'aliases' => [
            'SearchKeys\SearchParamsPluginManager' => 'SearchKeys\Search\Params\PluginManager',
        ],
    ],
];

return $config;

