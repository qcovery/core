<?php
namespace BeluginoCover\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'BeluginoCover\Controller\CoverController' => 'BeluginoCover\Controller\CoverControllerFactory',
        ],
        'aliases' => [
            'cover' => 'BeluginoCover\Controller\CoverController',
            'Cover' => 'BeluginoCover\Controller\CoverController',
        ],
    ],
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'BeluginoCover\Cover\Loader' => 'BeluginoCover\Cover\LoaderFactory',
        ],
    ],
];

return $config;

