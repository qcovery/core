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
	    'daiaplus',
        'extendedfacets',
        'libraries',
        'searchkeys',
        'dependentworks',
        'recorddriver',
        'helptooltips',
        'rvk',
        'beluga-core-base',
        'rvk',
        'storageinfo',
    ],
    "less" => [
        "active" => false,
        "compiled.less"
    ],
];
