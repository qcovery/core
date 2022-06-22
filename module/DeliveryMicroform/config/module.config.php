<?php
namespace DeliveryMicroform\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'DeliveryMicroform\Controller\DeliveryMicroformController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'DeliveryMicroform' => 'DeliveryMicroform\Controller\DeliveryMicroformController',
            'deliverymicroform' => 'DeliveryMicroform\Controller\DeliveryMicroformController',
        ],
    ],
];

$staticRoutes = [
    'DeliveryMicroform/Email', 'DeliveryMicroform/Home',
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

