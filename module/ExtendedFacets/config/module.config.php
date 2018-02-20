<?php
namespace ExtendedFacets\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recommend' => [
                'abstract_factories' => ['VuFind\Recommend\PluginFactory'],
                'factories' => [
                    'sidefacets' => 'ExtendedFacets\Recommend\Factory::getSideFacets',
                ],
            ],
        ],
    ],
];

return $config;

