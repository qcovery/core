<?php
namespace RecordDriver\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'RecordDriver\RecordDriver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            //'RecordDriver\RecordDriverPluginManager' => 'RecordDriver\RecordDriver\PluginManager',
            'VuFind\RecordDriver\PluginManager' => 'RecordDriver\RecordDriver\PluginManager',
        ],
    ],
];

return $config;

