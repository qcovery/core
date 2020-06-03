<?php
namespace PubPharm\Module\Configuration;

$config = [
    'controllers' => [
        'invokables' => [
            'pubpharm' => 'PubPharm\Controller\PubPharmController',
			'beluga' => 'PubPharm\Controller\PubPharmController',
        ],
    ],
];

// Define static routes -- Controller/Action strings
$staticRoutes = array(
   'Beluga/Help', 'PubPharm/Help', 'Beluga/Contact', 'PubPharm/Contact', 'Beluga/SearchTools', 'Beluga/SearchTools'
);

return $config;

