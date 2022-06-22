<?php
namespace PublicationSuggestion\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'PublicationSuggestion\Controller\PublicationSuggestionController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'PublicationSuggestion' => 'PublicationSuggestion\Controller\PublicationSuggestionController',
            'publicationsuggestion' => 'PublicationSuggestion\Controller\PublicationSuggestionController',
        ],
    ],
];

$staticRoutes = [
    'PublicationSuggestion/Email', 'PublicationSuggestion/Home'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
$routeGenerator->addDynamicRoutes($config, $dynamicRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

// Add the home route last
$config['router']['routes']['home'] = [
    'type' => 'Zend\Router\Http\Literal',
    'options' => [
        'route'    => '/',
        'defaults' => [
            'controller' => 'index',
            'action'     => 'Home',
        ]
    ]
];

return $config;

