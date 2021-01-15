<?php
namespace FulltextFinder\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'FulltextFinder\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'FulltextFinder\AjaxHandler\PluginManager',
        ],
    ],
];

return $config;

