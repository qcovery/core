<?php
namespace ExtendedFacets\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recommend' => [
                'factories' => [
                    'ExtendedFacets\Recommend\SideFacets' =>
                        'ExtendedFacets\Recommend\Factory::getSideFacets',
                ],
                'aliases' => [
                    'sidefacets' => 'ExtendedFacets\Recommend\SideFacets',
                ]
            ],
        ],
    ],
];

return $config;

