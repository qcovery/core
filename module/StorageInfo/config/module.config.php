<?php
namespace ResultFeedback\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'StorageInfo\Controller\StorageInfoController' => 'StorageInfo\Controller\StorageInfoControllerFactory',
        ],
        'aliases' => [
            'StorageInfo' => 'StorageInfo\Controller\StorageInfoController',
            'storageinfo' => 'StorageInfo\Controller\StorageInfoController',
        ],
    ],
];

return $config;

