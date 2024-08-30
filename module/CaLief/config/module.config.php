<?php
namespace CaLief\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'CaLief\Controller\CaLiefController' => 'CaLief\Controller\AbstractBaseWithConfigFactory',
        ],
        'aliases' => [
            'calief' => 'CaLief\Controller\CaLiefController',
            'Calief' => 'CaLief\Controller\CaLiefController',
            'CaLief' => 'CaLief\Controller\CaLiefController',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'db_row' => [
                'factories' => [
                    'CaLief\Db\Row\CaliefAdmin' => 'VuFind\Db\Row\RowGatewayFactory',
                    'CaLief\Db\Row\UserCalief' => 'VuFind\Db\Row\RowGatewayFactory',
                    'CaLief\Db\Row\UserCaliefLog' => 'VuFind\Db\Row\RowGatewayFactory',
                ],
                'aliases' => [
                    'usercalief' => 'CaLief\Db\Row\UserCalief',
                    'usercalieflog' => 'CaLief\Db\Row\UserCaliefLog',
                    'caliefadmin' => 'CaLief\Db\Row\CaliefAdmin',
                ],
            ],
            'db_table' => [
                'factories' => [
                    'CaLief\Db\Table\CaliefAdmin' => 'VuFind\Db\Table\GatewayFactory',
                    'CaLief\Db\Table\UserCalief' => 'VuFind\Db\Table\GatewayFactory',
                    'CaLief\Db\Table\UserCaliefLog' => 'VuFind\Db\Table\GatewayFactory',
                ],
                'aliases' => [
                    'caliefadmin' => 'CaLief\Db\Table\CaliefAdmin',
                    'usercalief' => 'CaLief\Db\Table\UserCalief',
                    'usercalieflog' => 'CaLief\Db\Table\UserCaliefLog',
                ],
            ],
        ],
    ],
];

// Define static routes -- Controller/Action strings
$staticRoutes = [
    'CaLief/Index'
];

$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);

return $config;
