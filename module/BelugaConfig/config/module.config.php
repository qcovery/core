<?php
namespace BelugaConfig\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'BelugaConfig\AjaxHandler\GetResultCount' =>
                        'BelugaConfig\AjaxHandler\GetResultCountFactory',
                ],
                'aliases' => [
                    'getResultCount' => 'BelugaConfig\AjaxHandler\GetResultCount',
                ]
            ],
        ],
    ],
];

return $config;

