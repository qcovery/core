<?php
return [
    'js' => ['delivery.js'],
    'helpers' => [
        'factories' => [
            'Delivery\View\Helper\Delivery\AvailabilityChecker' => 'Delivery\View\Helper\Delivery\AvailabilityCheckerFactory',
            'Delivery\View\Helper\Delivery\Authenticator' => 'Delivery\View\Helper\Delivery\AuthenticatorFactory',
        ],
        'aliases' => [
            'availabilitychecker' => 'Delivery\View\Helper\Delivery\AvailabilityChecker',
            'authenticator' => 'Delivery\View\Helper\Delivery\Authenticator',
        ]
    ]
];
