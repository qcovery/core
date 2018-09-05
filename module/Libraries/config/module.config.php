<?php
namespace Libraries\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'Libraries\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'Libraries\Search\BackendManager' => 'Libraries\Search\BackendManagerFactory',
            'Libraries\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'Libraries\Search\Results\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'Libraries\AjaxHandler\PluginManager',
            'VuFind\Search\BackendManager' => 'Libraries\Search\BackendManager',
            'VuFind\Search\Params\PluginManager' => 'Libraries\Search\Params\PluginManager',
            'VuFind\Search\Results\PluginManager' => 'Libraries\Search\Results\PluginManager',
        ],
    ],
];

return $config;

