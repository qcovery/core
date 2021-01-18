<?php
return [
    'js' => ['libraries.js'],
    'helpers' => [
        'factories' => [
            'Libraries\View\Helper\Libraries\ConnectedLibraries' => 'Libraries\View\Helper\Libraries\ConnectedLibrariesFactory',
        ],
        'aliases' => [
            'connectedlibraries' => 'Libraries\View\Helper\Libraries\ConnectedLibraries',
        ]
    ]
];
