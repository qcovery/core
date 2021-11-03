<?php
return [
    'css' => ['daiaplus.css'],
    'js' => [
        'check_item_statuses.js',
    ],
    'helpers' => [
        'factories' => [
            'AvailabilityPlus\Helper\DAIAplus\DAIAplusProcessor' => 'AvailabilityPlus\View\Helper\DAIAplus\DAIAplusProcessorFactory',
        ],
        'aliases' => [
            'DAIAplusProcessor' => 'Availability\View\Helper\DAIAplus\DAIAplusProcessor',
        ]
    ]
];
