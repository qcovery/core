<?php
namespace DAIAplus\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'DAIAplus\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'DAIAplus\ILS\Connection' => 'DAIAplus\ILS\ConnectionFactory',
            'DAIAplus\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'DAIAplus\AjaxHandler\PluginManager',
            'VuFind\ILSConnection' => 'DAIAplus\ILS\Connection',
            'VuFind\ILSDriverPluginManager' => 'DAIAplus\ILS\Driver\PluginManager',
        ],
    ],
];

return $config;

