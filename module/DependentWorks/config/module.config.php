<?php
namespace DependentWorks\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'DependentWorks\AjaxHandler\GetDependentWorks' =>
                        'DependentWorks\AjaxHandler\GetDependentWorksFactory',
                ],
                'aliases' => [
                    'getDependentWorks' => 'DependentWorks\AjaxHandler\GetDependentWorks',
                ]
            ],
        ],
    ],
];

return $config;

