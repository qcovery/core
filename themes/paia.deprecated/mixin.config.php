<?php
return [
    'helpers' => [
        'factories' => [
            'PAIA\PAIAHelper' => 'PAIA\PAIAHelperFactory',
        ],
        'aliases' => [
            'paia' => 'PAIA\PAIAHelper',
        ]
    ]
];