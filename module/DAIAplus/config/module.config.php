<?php
namespace DAIAplus\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'DAIAplus\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\ILSDriverPluginManager' => 'DAIAplus\ILS\Driver\PluginManager',
        ],
    ],
];

return $config;

