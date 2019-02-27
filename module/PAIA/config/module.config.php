<?php
namespace PAIA\Module\Configuration;

$config = array(
    'controllers' => array(
        'factories' => [
            'PAIA\Controller\MyResearchController' => 'PAIA\Controller\Factory',
            'PAIA\Controller\PAIAController' => 'PAIA\Controller\PAIAFactory',
        ],
        'aliases' => [
            'MyResearch' => 'PAIA\Controller\MyResearchController',
            'myresearch' => 'PAIA\Controller\MyResearchController',
            'PAIA' => 'PAIA\Controller\PAIAController',
            'paia' => 'PAIA\Controller\PAIAController',
        ],
    ),
    'controller_plugins' => [
        'factories' => [
            'PAIA\Controller\Plugin\Renewals' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ],
        'aliases' => [
            'renewals' => 'PAIA\Controller\Plugin\Renewals',
        ],
    ],
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
