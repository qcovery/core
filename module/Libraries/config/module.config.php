<?php
namespace Libraries\Module\Configuration;

$config = [
    'service_manager' => [
        'allow_override' => true,
        'factories' => [
            'Libraries\Search\BackendManager' => 'Libraries\Search\BackendManagerFactory',
        ],
        'aliases' => [
            'VuFind\Search\BackendManager' => 'Libraries\Search\BackendManager',
        ],
    ],
    'vufind' => [
        'plugin_managers' => [
            'ajaxhandler' => [
                'factories' => [
                    'Libraries\AjaxHandler\GetLibraries' =>
                        'Libraries\AjaxHandler\GetLibrariesFactory',
                ],
                'aliases' => [
                    'getLibraries' => 'Libraries\AjaxHandler\GetLibraries',
                ]
            ],
/*
            'search_backend' => [
                'delegators' => [
                    'Solr' => ['Libraries\Search\Factory\SolrDefaultBackendDelegatorFactory'],
                ]
            ],
*/
            'search_params' => [
                'factories' => [
                    'Libraries\Search\Search2\Params' => 'Libraries\Search\Solr\ParamsFactory',
                    'Libraries\Search\Solr\Params' => 'Libraries\Search\Solr\ParamsFactory',
                ],
                'aliases' => [
                    'search2' => 'Libraries\Search\Search2\Params',
                    'solr' => 'Libraries\Search\Solr\Params',
                ]
            ],
            'search_results' => [
                'factories' => [
                    'VuFind\Search\Search2\Results' => 'Libraries\Search\Search2\ResultsFactory',
                    'VuFind\Search\Solr\Results' => 'Libraries\Search\Solr\ResultsFactory',
                ],
                'aliases' => [
                    'search2' => 'VuFind\Search\Search2\Results',
                    'solr' => 'VuFind\Search\Solr\Results',
                ]
            ],
        ],
    ],

];

return $config;

