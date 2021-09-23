<?php
namespace ResultCount\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'ResultCount\AjaxHandler\GetResultCount' =>
                        'ResultCount\AjaxHandler\GetResultCountFactory',
                ],
                'aliases' => [
                    'getResultCount' => 'ResultCount\AjaxHandler\GetResultCount',
                ]
            ],
        ],
    ],
];

return $config;

