<?php
namespace DAIAplus\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'DAIAplus\AjaxHandler\GetArticleStatuses' =>
                        'DAIAplus\AjaxHandler\GetArticleStatusesFactory',
                    'DAIAplus\AjaxHandler\GetItemStatuses' =>
                        'DAIAplus\AjaxHandler\GetItemStatusesFactory',
                ],
                'aliases' => [
                    'getArticleStatuses' => 'DAIAplus\AjaxHandler\GetArticleStatuses',
                    'getItemStatuses' => 'DAIAplus\AjaxHandler\GetItemStatuses',
                ]
            ],
            'ils_driver' => [
                'factories' => [
                    'DAIAplus\ILS\Driver\DAIA' =>
                        'VuFind\ILS\Driver\DriverWithDateConverterFactory',
                    'DAIAplus\ILS\Driver\PAIA' =>
                        'DAIAplus\ILS\Driver\PAIAFactory',
                ],
                'aliases' => [
                    'daia' => 'DAIAplus\ILS\Driver\DAIA',
                    'paia' => 'DAIAplus\ILS\Driver\PAIA',
                ]
            ],
        ],
    ],
];

return $config;

