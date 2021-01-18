<?php
namespace PAIAplus\Module\Config;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'PAIAplus\Auth\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticatorFactory',
        ],
        'aliases' => [
            'VuFind\Auth\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticator',
            'VuFind\ILSAuthenticator' => 'PAIAplus\Auth\ILSAuthenticator',
        ],
    ],
    'controllers' => [
        'factories' => [
            'PAIAplus\Controller\RecordController' => 'PAIAplus\Controller\AbstractBaseWithConfigFactory',
        ],
        'aliases' => [
            'Record' => 'PAIAplus\Controller\RecordController',
            'record' => 'PAIAplus\Controller\RecordController',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ils_driver' => [
                'factories' => [
                    'PAIAplus\ILS\Driver\PAIA' => 'PAIAplus\ILS\Driver\PAIAFactory',
                ],
                'aliases' => [
                    'paia' => 'PAIAplus\ILS\Driver\PAIA',
                ]
            ],
        ],
    ],
];

return $config;
