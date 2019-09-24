<?php
return [
//    'js' => ['libraries.js'],
    'helpers' => [
        'factories' => [
            'RelevancePicker\View\Helper\RelevancePicker\Tooltip' => 'RelevancePicker\View\Helper\RelevancePicker\TooltipFactory',
        ],
        'aliases' => [
            'relevancetooltip' => 'RelevancePicker\View\Helper\RelevancePicker\Tooltip',
        ]
    ]
];
