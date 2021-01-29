<?php
return [
    'css' => [
        'availabilityfeedback.css'
    ],
    'helpers' => [
        'factories' => [
            'AvailabilityFeedback\View\Helper\AvailabilityFeedback\AvailabilityFeedback' => 'AvailabilityFeedback\View\Helper\AvailabilityFeedback\AvailabilityFeedbackFactory',
        ],
        'aliases' => [
            'AvailabilityFeedback' => 'AvailabilityFeedback\View\Helper\AvailabilityFeedback\AvailabilityFeedback',
        ]
    ]
];
