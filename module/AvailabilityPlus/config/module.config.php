<?php
namespace AvailabilityPlus\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'AvailabilityPlus\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'AvailabilityPlus\AjaxHandler\PluginManager',
        ],
    ],
];

return $config;

