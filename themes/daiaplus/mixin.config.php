<?php
return [
    'css' => ['daiaplus.css'],
    'js' => [
        'check_item_statuses.js',
        'daia.js',
    ],
    'helpers' => [
        'factories' => [
            'DAIAplus\View\Helper\DAIAplus\DAIAplus' => 'DAIAplus\View\Helper\DAIAplus\DAIAplusFactory',
        ],
        'aliases' => [
            'DAIAplus' => 'DAIAplus\View\Helper\DAIAplus\DAIAplus',
        ]
    ]
];