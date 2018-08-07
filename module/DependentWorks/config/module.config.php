<?php
namespace DependentWorks\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'DependentWorks\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'DependentWorks\AjaxHandler\PluginManager'
        ],
    ],
];

return $config;

