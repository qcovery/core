<?php

return [
    'helpers' => [
        'factories' => [
            'BelugaConfig\View\Helper\BelugaConfig\ConfigReader' => 'BelugaConfig\View\Helper\BelugaConfig\ConfigReaderFactory',
        ],
        'aliases' => [
            'configreader' => 'BelugaConfig\View\Helper\BelugaConfig\ConfigReader',
        ]
    ],
];
