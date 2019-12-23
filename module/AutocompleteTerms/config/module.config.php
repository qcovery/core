<?php
namespace AutocompleteTerms\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'AutocompleteTerms\Autocomplete\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\AutocompletePluginManager' => 'AutocompleteTerms\Autocomplete\PluginManager',
            'VuFind\Autocomplete\PluginManager' => 'AutocompleteTerms\Autocomplete\PluginManager',
        ],
    ],
];

return $config;
