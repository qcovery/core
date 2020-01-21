<?php
namespace RelevancePicker\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
//            'Libraries\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'RelevancePicker\Search\BackendManager' => 'RelevancePicker\Search\BackendManagerFactory',
            'RelevancePicker\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'RelevancePicker\Search\Results\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
//            'VuFind\AjaxHandler\PluginManager' => 'Libraries\AjaxHandler\PluginManager',
            'VuFind\Search\BackendManager' => 'RelevancePicker\Search\BackendManager',
            'VuFind\Search\Params\PluginManager' => 'RelevancePicker\Search\Params\PluginManager',
            'VuFind\Search\Results\PluginManager' => 'RelevancePicker\Search\Results\PluginManager',
        ],
    ],
];

return $config;

