<?php
namespace FulltextFinder\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'FulltextFinder\AjaxHandler\GetFulltextFinder' => 'FulltextFinder\AjaxHandler\GetFulltextFinderFactory',
                ],
                'aliases' => [
                    'getFulltextFinder' => 'FulltextFinder\AjaxHandler\GetFulltextFinder',
                ],
            ],
        ],
    ],
];

return $config;

