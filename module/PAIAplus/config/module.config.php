<?php
namespace PAIAplus\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'PAIAplus\Auth\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticatorFactory',
            'PAIAplus\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'PAIAplus\ILS\Connection' => 'PAIAplus\ILS\ConnectionFactory',
        ],
        'aliases' => [
            'VuFind\Auth\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticator',
            'VuFind\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticator',
            'VuFind\ILS\Driver\PluginManager' => 'PAIAplus\ILS\Driver\PluginManager',
            'VuFind\ILSDriverPluginManager' => 'PAIAplus\ILS\Driver\PluginManager',
            'VuFind\ILS\Connection' => 'PAIAplus\ILS\Connection',
            'VuFind\ILSConnection' => 'PAIAplus\ILS\Connection',
        ],
    ],
    'controllers' => [
        'factories' => [
            'PAIAplus\Controller\RecordController' => 'PAIAplus\Controller\AbstractBaseWithConfigFactory',
            'PAIAplus\Controller\MyResearchController' => 'PAIAplus\Controller\MyResearchControllerFactory',
        ],
        'aliases' => [
            'Record' => 'PAIAplus\Controller\RecordController',
            'record' => 'PAIAplus\Controller\RecordController',
            'MyResearch' => 'PAIAplus\Controller\MyResearchController',
            'myresearch' => 'PAIAplus\Controller\MyResearchController',
        ],
    ],
];

return $config;
