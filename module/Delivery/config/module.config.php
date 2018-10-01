<?php
namespace Delivery\Module\Configuration;

$config = [
/*
    'controllers' => [
        'factories' => [
            'Delivery\Controller\DeliveryController' => 'Delivery\Controller\Factory::getDeliveryController',
        ],
        'aliases' => [
            'delivery' => 'Delivery\Controller\DeliveryController',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Delivery\DbTablePluginManager' => 'Delivery\Service\Factory::getDbTablePluginManager',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'db_table' => [
                'factories' => [
                    'userdelivery' => 'Delivery\Db\Table\Factory::getUserDelivery',
                ],
            ],
            'recorddriver' => [
//                'abstract_factories' => ['VuFind\RecordDriver\PluginFactory'],
                'factories' => [
                    'solrmarc' => 'Delivery\RecordDriver\Factory::getSolrMarc',
//                    'findex' => 'Libraries\RecordDriver\Factory::getSolrMarc',
                ],
            ],
        ],
    ],
*/
];

// Define static routes -- Controller/Action strings
$staticRoutes = [
   'Delivery/Home', 'Delivery/Edit', 'Delivery/Register', 'Delivery/Admin', 'Delivery/Order'
];
/*
$routeGenerator = new \VuFind\Route\RouteGenerator();
$routeGenerator->addStaticRoutes($config, $staticRoutes);
*/
return $config;
