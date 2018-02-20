<?php

return [
    'extends' => 'bootstrap3',
    'css' => [
        'jquery.qtip.min.css',
        'uikit.min.css',
        'compiled.css',
        'belugino.css',
    ],
    'js' => [
        'jquery.qtip.min.js',
        'uikit.min.js',
        'belugax.js',
    ],
    'mixins' => [
        'delivery',
        'libraries',
        'findex',
        'searchkeys',
        'dependentworks',
        'daia',
        'recorddriver',
        'belugadefault',
    ],
    'helpers' => [
        'factories' => [
            'configreader' => 'BelugaConfig\View\Helper\BelugaConfig\Factory::getConfigReader',
        ]
    ],
    "less" => [
        "active" => false,
        "compiled.less"
    ],
];
