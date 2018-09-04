<?php
namespace PAIA\Module\Configuration;

$config = array(
    'controllers' => array(
        'invokables' => array(
            'paia' => 'PAIA\Controller\PAIAController',
            'MyResearch' => 'PAIA\Controller\MyResearchController',
        ),
        'factories' => [
            'PAIA\Controller\MyResearchController' => 'PAIA\Controller\Factory::getMyResearchController',
        ],
        'aliases' => [
            'MyResearch' => 'PAIA\Controller\MyResearchController',
            'myresearch' => 'PAIA\Controller\MyResearchController',
        ],
    ),
    'service_manager' => array(
        'factories' => array(
            'VuFind\ILSHoldLogic' => 'PAIA\Service\Factory::getILSHoldLogic',
            'VuFind\ILSTitleHoldLogic' => 'PAIA\Service\Factory::getILSTitleHoldLogic',
//            'VuFind\AuthManager' => 'PAIA\Auth\Factory::getManager',
        ),
    ),
//    'vufind' => array(
//        'plugin_managers' => array(
//            'auth' => [
//                'factories' => [
//                    'ils' => 'PAIA\Auth\Factory::getILS',
//                ],
//            ],
//            'ils_driver' => array(
//                'factories' => [
//                    'paia' => 'PAIA\ILS\Driver\Factory::getPaia',
//                ],
//            ),
//            'recorddriver' => array(
//                'factories' => array(
//                    'solrdefault' => 'PAIA\RecordDriver\Factory::getSolrDefault',
//               ),
//            ),
//        ),
//    ),
);

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'PAIA\Auth\Manager' => 'VuFind\Auth\ManagerFactory',
            'PAIA\ILS\Driver\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
        ],
        'aliases' => [
            'VuFind\Auth\Manager' => 'PAIA\Auth\Manager',
            'VuFind\ILS\Driver\PluginManager' => 'PAIA\ILS\Driver\PluginManager',
        ]
    ]
];

return $config;
