<?php
namespace Findex\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'search_backend' => [
                'factories' => [
                    'Findex' => 'Findex\Search\Factory\FindexBackendFactory',
                ],
            ],
        ],
    ],
];

$recordRoutes = [
    'findexrecord' => 'FindexRecord',
];

$staticRoutes = [
    'Findex/Advanced', 'Findex/Home', 'Findex/Search',
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;

