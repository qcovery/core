<?php
namespace Resolver\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'resolver_driver' => [
                'factories' => [
                    \Resolver\Resolver\Driver\KVK::class =>
                        \VuFind\Resolver\Driver\AbstractBaseFactory::class],
                'aliases' => [
                    'kvk' => \Resolver\Resolver\Driver\KVK::class,
                ]
            ],
        ],
    ],
];

return $config;

