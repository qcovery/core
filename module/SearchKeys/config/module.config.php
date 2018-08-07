<?php
namespace SearchKeys\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'SearchKeys\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'SearchKeys\Search\SearchKeysHelper' => 'SearchKeys\Search\SearchKeysHelperFactory',
        ],
        'aliases' => [
            'VuFind\Search\Params\PluginManager' => 'SearchKeys\Search\Params\PluginManager',
        ],
    ],
];

return $config;

