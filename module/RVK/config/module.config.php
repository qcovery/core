<?php

return array (
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'RVK\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'RVK\AjaxHandler\PluginManager',
        ],
    ],
);