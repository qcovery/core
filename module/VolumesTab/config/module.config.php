<?php
namespace VolumesTab\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver' => [
                'factories' => [
                    'VolumesTab\RecordDriver\SolrMarc' => 'RecordDriver\RecordDriver\SolrDefaultFactory',
                ],
                'aliases' => [
                    'VuFind\RecordDriver\SolrMarc' => 'VolumesTab\RecordDriver\SolrMarc',
                ],
            ],
            'recordtab' => [
                'factories' => [
                    'VolumesTab\RecordTab\Volumes' => 'VolumesTab\RecordTab\VolumesFactory',
                ],
                'aliases' => [
                    'Volumes' => 'VolumesTab\RecordTab\Volumes',
                    'volumes' => 'VolumesTab\RecordTab\Volumes',
                ],
            ],
        ],
    ],
];

return $config;

