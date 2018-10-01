<?php
return [
    'helpers' => [
        'factories' => [
            'Delivery\View\Helper\Delivery\AvailabilityChecker' => 'Delivery\View\Helper\Delivery\AvailabilityCheckerFactory',
        ],
        'aliases' => [
            'availabilitychecker' => 'Delivery\View\Helper\Delivery\AvailabilityChecker',
        ]
    ]
];
