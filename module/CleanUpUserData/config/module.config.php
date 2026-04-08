<?php
$config = [
    'vufind' => [
        'plugin_managers' => [
            'command' => [
                'factories' => [
                    'CleanUpUserData\Command\Util\CleanUpUserDataCommand' => 'CleanUpUserData\Command\Util\CleanUpUserDataCommandFactory',
                ],
                'aliases' => [
                    'util/cleanupuserdata' => 'CleanUpUserData\Command\Util\CleanUpUserDataCommand',
                ],
            ],
        ],
    ],
];

return $config;