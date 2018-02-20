<?php
return [
    'js' => ['libraries.js'],
    'helpers' => [
        'factories' => [
            'connectedlibraries' => 'Libraries\View\Helper\Libraries\Factory::getConnectedLibraries',
        ]
    ]
];
