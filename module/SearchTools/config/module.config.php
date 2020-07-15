<?php
namespace ResultFeedback\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'SearchTools\Controller\SearchToolsController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'SearchTools' => 'SearchTools\Controller\SearchToolsController',
            'searchtools' => 'SearchTools\Controller\SearchToolsController',
        ],
    ],
];

$staticRoutes = [
    'SearchTools/SearchTool', 'SearchTools/RecoverPassword', 'SearchTools/StructureSearch'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;

