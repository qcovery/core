<?php
namespace LMS\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'LMS\Cart' => 'LMS\CartFactory',
        ],
        'aliases' => [
            'VuFind\Cart' => 'LMS\Cart',
        ],
    ],
    'controllers' => [
        'factories' => [
            'LMS\Controller\CartController' => 'LMS\Controller\CartControllerFactory',
            'LMS\Controller\MyResearchController' => 'LMS\Controller\MyResearchControllerFactory',
        ],
        'aliases' => [
            'VuFind\Controller\CartController' => 'LMS\Controller\CartController',
            'MyResearch' => 'LMS\Controller\MyResearchController',
            'myresearch' => 'LMS\Controller\MyResearchController',
        ],
    ],
];

return $config;
