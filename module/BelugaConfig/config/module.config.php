<?php
namespace BelugaConfig\Module\Configuration;

$config = [
    'vufind' => [
        'recorddriver_tabs' => [
            'VuFind\RecordDriver\Primo' => [
                'tabs' => null,
                'defaultTab' => null,
            ],
            'VuFind\RecordDriver\DefaultRecord' => [
                'tabs' => null,
                'defaultTab' => null,
            ],
            'VuFind\RecordDriver\SolrMarc' => [
                'tabs' => null,
                'defaultTab' => null,
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'BelugaConfig\Controller\AjaxController' => 'BelugaConfig\Controller\Factory::getAjaxController',
        ],
        'aliases' => [
            'AJAX' => 'BelugaConfig\Controller\AjaxController',
            'ajax' => 'BelugaConfig\Controller\AjaxController',
        ],
    ],
];

return $config;

