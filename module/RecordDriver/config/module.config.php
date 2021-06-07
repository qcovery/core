<?php
namespace RecordDriver\Module\Config;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'delegators' => [
                    \RecordDriver\RecordDriver\SolrMarc::class => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ],
                'factories' => [
                    \RecordDriver\RecordDriver\SolrMarc::class => \RecordDriver\RecordDriver\SolrDefaultFactory::class,
                ],
                'aliases' => [
                    'solrmarc' => \RecordDriver\RecordDriver\SolrMarc::class,
                ]
            ],
        ],
    ],
];

return $config;

