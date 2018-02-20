<?php
namespace RecordDriver\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'abstract_factories' => ['VuFind\RecordDriver\PluginFactory'],
                'factories' => [
                    'solrmarc' => 'RecordDriver\RecordDriver\Factory::getSolrMarc',
                ],
            ],
        ],
    ],
];

return $config;

