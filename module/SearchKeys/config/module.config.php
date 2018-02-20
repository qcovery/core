<?php
namespace SearchKeys\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'search_params' => [
                'abstract_factories' => ['SearchKeys\Search\Params\PluginFactory'],
                'factories' => [
                    'solr' => 'SearchKeys\Search\Params\Factory::getSolr',
                    'primo' => 'SearchKeys\Search\Params\Factory::getPrimo',
                    'findex' => 'SearchKeys\Search\Params\Factory::getFindex',
                ],
            ],
        ],
    ],
];

return $config;

