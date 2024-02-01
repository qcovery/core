<?php
return [
    'extends' => 'bootstrap3',
    'css' => [
        'compiled.css',
        'belugino.css',
	    'base.css',
        'redesign.css',
    ],
    'js' => [
        'base.js',
    ],
    'helpers' => [
        'factories' => [
            'UISettings\View\Helper\UISettings\UISettings' => 'UISettings\View\Helper\UISettings\UISettingsFactory',
            'HeadTitle\View\Helper\Root\HeadTitle' => 'HeadTitle\View\Helper\Root\HeadTitleFactory',
            'VuFind\View\Helper\Root\RecordDataFormatter' => 'MPG\View\Helper\Root\RecordDataFormatterFactory',
        ],
        'aliases' => [
            'uisettings' => 'UISettings\View\Helper\UISettings\UISettings',
            'headTitle' => 'HeadTitle\View\Helper\Root\HeadTitle',
            'recordDataFormatter' => 'VuFind\View\Helper\Root\RecordDataFormatter',
        ],
    ],
    'mixins' => [
        'belugaconfig',
        //'beluginocover',
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
        'autocompleteterms',

    ],
];