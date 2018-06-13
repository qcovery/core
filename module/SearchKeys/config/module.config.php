<?php
namespace SearchKeys\Module\Configuration;

$config = [
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
];

return $config;

