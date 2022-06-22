<?php
return [
    'css' => [
        'deliverymicroform.css',
        'jquery.datetimepicker.min.css'
    ],
    'js' => [
        'jquery.datetimepicker.full.min.js'
    ],
    'helpers' => [
        'factories' => [
            'DeliveryMicroform\View\Helper\DeliveryMicroform\DeliveryMicroform' => 'DeliveryMicroform\View\Helper\DeliveryMicroform\DeliveryMicroformFactory',
        ],
        'aliases' => [
            'DeliveryMicroform' => 'DeliveryMicroform\View\Helper\DeliveryMicroform\DeliveryMicroform',
        ]
    ]
];
