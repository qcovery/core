<?php
namespace ListAdmin\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'ListAdmin\Controller\ListAdminController' => 'ListAdmin\Controller\ListAdminControllerFactory',
        ],
        'aliases' => [
            'VuFind\Controller\ListAdminController' => 'ListAdmin\Controller\ListAdminController',
            'ListAdmin' => 'ListAdmin\Controller\ListAdminController',
            'listadmin' => 'ListAdmin\Controller\ListAdminController',
        ],
    ],
];

// Define static routes -- Controller/Action strings
$staticRoutes = [
    'ListAdmin/migrate'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;

