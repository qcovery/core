<?php
namespace RecordDriver\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'recorddriver' => [
                'RecordDriver\RecordDriver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            ],
        ],
        'aliases' => [
            'RecordDriver\RecordDriverPluginManager' => 'RecordDriver\RecordDriver\PluginManager',
        ],
    ],
];

return $config;

