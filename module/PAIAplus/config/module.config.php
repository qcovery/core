<?php
namespace PAIAplus\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'PAIAplus\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'PAIAplus\ILS\Connection' => 'PAIAplus\ILS\ConnectionFactory',
        ],
        'aliases' => [
            'VuFind\ILS\Driver\PluginManager' => 'PAIAplus\ILS\Driver\PluginManager',
            'VuFind\ILSDriverPluginManager' => 'PAIAplus\ILS\Driver\PluginManager',
            'VuFind\ILS\Connection' => 'PAIAplus\ILS\Connection',
            'VuFind\ILSConnection' => 'PAIAplus\ILS\Connection',
        ],
    ],
];

return $config;
