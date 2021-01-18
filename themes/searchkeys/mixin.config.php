<?php
return [
    'helpers' => [
        'factories' => [
            'SearchKeys\View\Helper\SearchKeys\SearchBox' => 'SearchKeys\View\Helper\SearchKeys\SearchBoxFactory',
        ],
        'aliases' => [
            'searchbox' => 'SearchKeys\View\Helper\SearchKeys\SearchBox',
        ],
    ],
];
