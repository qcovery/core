<?php
namespace RecordDriver\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'RecordDriver\RecordDriver\PluginManager' => 'RecordDriver\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'RecordDriver\RecordDriverPluginManager' => 'RecordDriver\RecordDriver\PluginManager',
        ],
    ],
];

return $config;

