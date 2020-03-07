<?php
namespace CaLief\Module\Configuration;

$config = array(
    'controllers' => [
        'factories' => [
            'CaLief\Controller\CaLiefController' => 'CaLief\Controller\AbstractBaseWithConfigFactory',
        ],
        'aliases' => [
            'calief' => 'CaLief\Controller\CaLiefController',
            'Calief' => 'CaLief\Controller\CaLiefController',
            'CaLief' => 'CaLief\Controller\CaLiefController',
        ],
    ],
    'view_helpers' => array(
        'invokables' => array(
            //'calief' => 'CaLief\CaLief\CaLiefHelper',
        )
    ),
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'CaLief\Db\Table\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'CaLief\Db\Row\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\Db\Table\PluginManager' => 'CaLief\Db\Table\PluginManager',
            'VuFind\Db\Row\PluginManager' => 'CaLief\Db\Row\PluginManager',
        ],
    ],

);

// Define static routes -- Controller/Action strings
$staticRoutes = [
    'CaLief/Index'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
