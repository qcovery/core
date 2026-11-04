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
    'vufind' => [
        'plugin_managers' => [
            'navigation' => [
                'factories' => [
                    'ListAdmin\Navigation\AccountMenu' => 'ListAdmin\Navigation\AccountMenuFactory',
                ],
                'aliases' => [
                    'VuFind\Navigation\AccountMenu' => 'ListAdmin\Navigation\AccountMenu',
                ],
            ],
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

