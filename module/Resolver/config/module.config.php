<?php
namespace Resolver\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'Resolver\AjaxHandler\GetResolverLinks' =>
                        'Resolver\AjaxHandler\GetResolverLinksFactory',
                ],
                'aliases' => [
                    'getResolverLinks' => 'Resolver\AjaxHandler\GetResolverLinks',
                ]
            ],
            'resolver_driver' => [
                'factories' => [
                    'Resolver\Resolver\Driver\KVK' =>
                        'VuFind\Resolver\Driver\AbstractBaseFactory',
                    'Resolver\Resolver\Driver\Ezb' =>
                        \VuFind\Resolver\Driver\EzbFactory::class,
                    'Resolver\Resolver\Driver\JOP' =>
                        'Resolver\Resolver\Driver\JOPFactory',
                    'Resolver\Resolver\Driver\HBZ' =>
                        'VuFind\Resolver\Driver\AbstractBaseFactory',
                ],
                'aliases' => [
                    'kvk' => 'Resolver\Resolver\Driver\KVK',
                    'ezb' => 'Resolver\Resolver\Driver\Ezb',
                    'VuFind\Resolver\Driver\Ezb' => 
                        'Resolver\Resolver\Driver\Ezb',
                    'jop' => 'Resolver\Resolver\Driver\JOP',
                    'hbz' => 'Resolver\Resolver\Driver\HBZ',
                ]
            ],
        ],
    ],
];

return $config;

