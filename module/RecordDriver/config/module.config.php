<?php
namespace RecordDriver\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'RecordDriver\RecordDriver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\RecordDriver\PluginManager' => 'RecordDriver\RecordDriver\PluginManager',
        ],
    ],
];

return $config;

