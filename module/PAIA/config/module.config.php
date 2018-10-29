<?php
namespace PAIA\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => [
            'PAIA\Controller\MyResearchController' => 'PAIA\Controller\Factory',
        ],
        'aliases' => [
            'MyResearch' => 'PAIA\Controller\MyResearchController',
            'myresearch' => 'PAIA\Controller\MyResearchController',
        ],
    ),
    'service_manager' => array(
        'allow_override' => true,
        'factories' => array(
            //'VuFind\ILSHoldLogic' => 'PAIA\Service\Factory::getILSHoldLogic',
            //'VuFind\ILSTitleHoldLogic' => 'PAIA\Service\Factory::getILSTitleHoldLogic',
            //'VuFind\AuthManager' => 'PAIA\Auth\Factory::getManager',
            'PAIA\ILS\Connection' => 'PAIA\ILS\ConnectionFactory',
            'PAIA\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'PAIA\Auth\Manager' => 'PAIA\Auth\ManagerFactory',
            'PAIA\Auth\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'PAIA\Auth\ILSAuthenticator' => 'PAIA\Auth\ILSAuthenticatorFactory',
        ),
        'aliases' => [
            'VuFind\ILSConnection' => 'PAIA\ILS\Connection',
            'VuFind\ILSDriverPluginManager' => 'PAIA\ILS\Driver\PluginManager',
            'VuFind\AuthManager' => 'PAIA\Auth\Manager',
            'VuFind\Auth\Manager' => 'PAIA\Auth\Manager',
            'VuFind\AuthPluginManager' => 'PAIA\Auth\PluginManager',
            'VuFind\ILSAuthenticator' => 'PAIA\Auth\ILSAuthenticator',
        ],
    ),
);

return $config;
