<?php
return [
    'css' => ['sfx.css'],
    'js' => ['sfx.js'],
    'helpers' => [
        'factories' => [
            'SFX\View\Helper\SFX\SFX' => 'SFX\View\Helper\SFX\SFXFactory',
        ],
        'aliases' => [
            'SFX' => 'SFX\View\Helper\SFX\SFX',
        ]
    ]
];
