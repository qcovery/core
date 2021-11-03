<?php
namespace AvailabilityPlus\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'AvailabilityPlus\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'AvailabilityPlus\ILS\Connection' => 'AvailabilityPlus\ILS\ConnectionFactory',
            'AvailabilityPlus\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'AvailabilityPlus\AjaxHandler\PluginManager',
            'VuFind\ILSConnection' => 'AvailabilityPlus\ILS\Connection',
            'VuFind\ILSDriverPluginManager' => 'AvailabilityPlus\ILS\Driver\PluginManager',
        ],
    ],
];

return $config;

