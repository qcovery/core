<?php
return [
    'css' => ['daiaplus.css'],
    'js' => [
        'check_item_statuses.js',
    ],
    'helpers' => [
        'factories' => [
            'DAIAplus\View\Helper\DAIAplus\DAIAplusProcessor' => 'DAIAplus\View\Helper\DAIAplus\DAIAplusProcessorFactory',
        ],
        'aliases' => [
            'DAIAplusProcessor' => 'DAIAplus\View\Helper\DAIAplus\DAIAplusProcessor',
        ]
    ]
];
