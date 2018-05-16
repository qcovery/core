<?php

return [
    'extends' => 'bootstrap3',
    'css' => [
        'jquery.qtip.min.css',
        'uikit.min.css',
        'compiled.css',
        'belugino.css',
        'belugax.css',
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
            'recordDataFormatter' => 'BelugaConfig\View\Helper\Root\RecordDataFormatterFactory',
        ]
    ],
    "less" => [
        "active" => false,
        "compiled.less"
    ],
];
