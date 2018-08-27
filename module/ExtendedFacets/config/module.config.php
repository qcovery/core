<?php
namespace ExtendedFacets\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'ExtendedFacets\Recommend\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\Recommend\PluginManager' => 'ExtendedFacets\Recommend\PluginManager',
        ],
    ],
];

return $config;

