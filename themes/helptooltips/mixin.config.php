<?php
return [
    'css' => [
        'tooltipster.bundle.min.css',
        'helptooltips.css',
    ],
    'js' => [
        'tooltipster.bundle.min.js',
        'helptooltips.js',
    ],
    'helpers' => [
        'factories' => [
            'HelpTooltips\View\Helper\HelpTooltips\HelpTooltips' => 'HelpTooltips\View\Helper\HelpTooltips\HelpTooltipsFactory',
        ],
        'aliases' => [
            'HelpTooltips' => 'HelpTooltips\View\Helper\HelpTooltips\HelpTooltips',
        ]
    ]
];
