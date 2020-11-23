<?php
namespace FacetPrefix\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'search_params' => [
                'factories' => [
                    'FacetPrefix\Search\Search2\Params' => 'FacetPrefix\Search\Solr\ParamsFactory',
                    'FacetPrefix\Search\Solr\Params' => 'FacetPrefix\Search\Solr\ParamsFactory',
                ],
                'aliases' => [
                    'search2' => 'FacetPrefix\Search\Search2\Params',
                    'solr' => 'FacetPrefix\Search\Solr\Params',
                ]
            ],
        ],
    ],
];

return $config;

