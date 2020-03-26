<?php
return [
    'helpers' => [
        'factories' => [
            'Notifications\View\Helper\Notifications\Notifier' => 'Notifications\View\Helper\Notifications\NotifierFactory',
        ],
        'aliases' => [
            'notifier' => 'Notifications\View\Helper\Notifications\Notifier',
        ]
    ]
];
