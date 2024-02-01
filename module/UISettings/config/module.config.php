<?php
$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'UISettings\AjaxHandler\SetUISettings' => 'UISettings\AjaxHandler\SetUISettingsFactory',
                ],
                'aliases' => [
                    'setUISettings' => 'UISettings\AjaxHandler\SetUISettings',
                ],
            ],
        ],
    ],
];

return $config;