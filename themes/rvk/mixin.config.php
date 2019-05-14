<?php
return [
    'css' => [
        'rvk.css',
        'jstree/themes/default/style.css',
    ],
    'js' => [
        'rvk.js',
        'jstree.min.js',
    ],
    'helpers' => [
        'factories' => [
            'RVK\View\Helper\RVK\RVK' => 'RVK\View\Helper\RVK\RVKFactory',
        ],
        'aliases' => [
            'RVK' => 'RVK\View\Helper\RVK\RVK',
        ]
    ]
];
