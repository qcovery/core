<?php
return [
    'css' => ['rvk.css'],
    'js' => ['rvk.js'],
    'helpers' => [
        'factories' => [
            'RVK\View\Helper\RVK\RVK' => 'RVK\View\Helper\RVK\RVKFactory',
        ],
        'aliases' => [
            'RVK' => 'RVK\View\Helper\RVK\RVK',
        ]
    ]
];
