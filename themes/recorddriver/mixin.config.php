<?php
return [
    'helpers' => [
        'factories' => [
            'RecordDriver\View\Helper\RecordDriver\SolrDetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetailsFactory',
        ],
        'aliases' => [
            'solrdetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetails',
        ]
    ]
];
