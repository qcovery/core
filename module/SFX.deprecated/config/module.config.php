<?php

return array (
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'SFX\AjaxHandler\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AjaxHandler\PluginManager' => 'SFX\AjaxHandler\PluginManager',
        ],
    ],
);