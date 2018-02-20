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
];

return $config;

