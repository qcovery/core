<?php
namespace IMSExport\Module\Configuration;

return array (
    'controllers' => array(
        'factories' => [
            'IMS\Controller\IMSController' => 'IMS\Controller\IMSFactory',
        ],
        'invokables' => [
            'IMS' => 'IMS\Controller\IMSController',
        ],
    ),
);