<?php
namespace FacetPrefix\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'FacetPrefix\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'FacetPrefix\Search\Results\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\Search\Params\PluginManager' => 'FacetPrefix\Search\Params\PluginManager',
            'VuFind\Search\Results\PluginManager' => 'FacetPrefix\Search\Results\PluginManager',
        ],
    ],
];

return $config;

