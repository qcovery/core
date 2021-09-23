<?php
return [
return [
    'js' => 'extendedfacets.js',
    'helpers' => [
        'factories' => [
            'ExtendedFacets\View\Helper\ExtendedFacets\ExtendedFacets' => 'ExtendedFacets\View\Helper\ExtendedFacets\ExtendedFacetsFactory',
        ],
        'aliases' => [
            'ExtendedFacets' => 'ExtendedFacets\View\Helper\ExtendedFacets\ExtendedFacets',
        ]
    ]
];
