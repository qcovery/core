<?php
return [
    'js' => ['get_holdings.js'],
    'helpers' => [
        'factories' => [
            'OpacScraper\View\Helper\OpacScraper\Holders' => 'OpacScraper\View\Helper\OpacScraper\HoldersFactory',
        ],
        'aliases' => [
            'holders' => 'OpacScraper\View\Helper\OpacScraper\Holders',
        ]
    ]
];
