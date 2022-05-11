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
        'paiaplus',
        'fulltextfinder',
        'beluga-core-base',
        'rvk',
        'storageinfo',
        'listadmin',
        'availabilityfeedback',
	    'publicationsuggestion',
    ],
    "less" => [
        "active" => false,
        "compiled.less"
    ],
];
