<?php
return [
    'css' => [
        'fulltextfinder.css',
    ],
    'js' => [
        'fulltextfinder.js',
    ],
    'helpers' => [
        'factories' => [
            'FulltextFinder\View\Helper\FulltextFinder\FulltextFinder' => 'FulltextFinder\View\Helper\FulltextFinder\FulltextFinderFactory',
        ],
        'aliases' => [
            'FulltextFinder' => 'FulltextFinder\View\Helper\FulltextFinder\FulltextFinder',
        ]
    ]
];
