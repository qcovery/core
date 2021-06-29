<?php
namespace OpacScraper\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'OpacScraper\AjaxHandler\GetHoldings' =>
                        'OpacScraper\AjaxHandler\GetHoldingsFactory',
                ],
                'aliases' => [
                    'getHoldings' => 'OpacScraper\AjaxHandler\GetHoldings',
                ]
            ],
            'ils_driver' => [
                'factories' => [
                    'OpacScraper\ILS\Driver\OpacScraper' =>
                        'OpacScraper\ILS\Driver\OpacScraperFactory',
                ],
                'aliases' => [
                    'opacscraper' => 'OpacScraper\ILS\Driver\OpacScraper',
                ]
            ],
        ],
    ],
];

return $config;

