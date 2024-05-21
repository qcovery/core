<?php
namespace AvailabilityFeedback\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'AvailabilityFeedback\Controller\AvailabilityFeedbackController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'AvailabilityFeedback' => 'AvailabilityFeedback\Controller\AvailabilityFeedbackController',
            'availabilityfeedback' => 'AvailabilityFeedback\Controller\AvailabilityFeedbackController',
        ],
    ],
];

$staticRoutes = [
    'AvailabilityFeedback/Email', 'AvailabilityFeedback/Home'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
$routeGenerator->addDynamicRoutes($config, $dynamicRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

// Add the home route last
$config['router']['routes']['home'] = [
    'type' => 'Laminas\Router\Http\Literal',
    'options' => [
        'route'    => '/',
        'defaults' => [
            'controller' => 'index',
            'action'     => 'Home',
        ]
    ]
];

return $config;

