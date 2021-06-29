<?php
return [
    'js' => ['openurl.js'],
    'helpers' => [
        'factories' => [
            'Resolver\View\Helper\Resolver\OpenUrl' => 'Resolver\View\Helper\Resolver\OpenUrlFactory',
        ],
        'aliases' => [
            'openUrl' => 'Resolver\View\Helper\Resolver\OpenUrl',
        ]
    ]
];
