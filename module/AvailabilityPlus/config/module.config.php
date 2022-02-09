<?php
namespace AvailabilityPlus\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'AvailabilityPlus\Controller\AvailabilityPlusController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'AvailabilityPlus' => 'AvailabilityPlus\Controller\AvailabilityPlusController',
            'availabilityplus' => 'AvailabilityPlus\Controller\AvailabilityPlusController',
        ],
    ],
];

$staticRoutes = [
    'AvailabilityPlus/TestCases'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;