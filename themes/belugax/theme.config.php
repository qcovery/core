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
        'belugaconfig',
        'delivery',
        'libraries',
        'searchkeys',
        'dependentworks',
//        'daia',
        'recorddriver',
        'belugadefault',
    ],
    'helpers' => [
        'factories' => [
            'BelugaConfig\View\Helper\BelugaConfig\ConfigReader' => 'BelugaConfig\View\Helper\BelugaConfig\ConfigReaderFactory',
        ],
        'aliases' => [
            'configreader' => 'BelugaConfig\View\Helper\BelugaConfig\ConfigReader',
//            'recordDataFormatter' => 'BelugaConfig\View\Helper\BelugaConfig\RecordDataFormatterFactory',
        ]
    ],
    "less" => [
        "active" => false,
        "compiled.less"
    ],
];
