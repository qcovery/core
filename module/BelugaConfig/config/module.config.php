<?php
namespace BelugaConfig\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'BelugaConfig\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'BelugaConfig\AjaxHandler\PluginManager',
        ],
    ],
];

return $config;

