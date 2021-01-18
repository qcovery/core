<?php
namespace SearchKeys\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'SearchKeys\Search\SearchKeysHelper' => 'SearchKeys\Search\SearchKeysHelperFactory',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'search_params' => [
                'factories' => [
                    'SearchKeys\Search\Search2\Params' => 'SearchKeys\Search\Solr\ParamsFactory',
                    'SearchKeys\Search\Solr\Params' => 'SearchKeys\Search\Solr\ParamsFactory',
                ],
                'aliases' => [
                    'search2' => 'SearchKeys\Search\Search2\Params',
                    'solr' => 'SearchKeys\Search\Solr\Params',
                ]
            ],
        ],
    ],
];

return $config;

