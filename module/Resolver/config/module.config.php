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
                    \Resolver\Resolver\Driver\KVK::class =>
                        \VuFind\Resolver\Driver\AbstractBaseFactory::class,
                    \Resolver\Resolver\Driver\JOP::class =>
                        \Resolver\Resolver\Driver\JOPFactory::class
                ],
                'aliases' => [
                    'kvk' => \Resolver\Resolver\Driver\KVK::class,
                    'jop' => \Resolver\Resolver\Driver\JOP::class,
                ]
            ],
        ],
    ],
];

return $config;

