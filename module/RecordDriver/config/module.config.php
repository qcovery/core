<?php
namespace RecordDriver\Module\Config;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'RecordDriver\RecordDriver\SolrMarc' =>
                        'RecordDriver\RecordDriver\SolrDefaultFactory',
                ],
                'aliases' => [
                    'solrmarc' => 'RecordDriver\RecordDriver\SolrMarc',
                ]
            ],
        ],
    ],
];

return $config;

