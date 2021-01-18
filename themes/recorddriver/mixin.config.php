<?php
return [
    'helpers' => [
        'factories' => [
            'RecordDriver\View\Helper\RecordDriver\SolrDetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetailsFactory',
        ],
        'aliases' => [
            'solrDetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetails',
        ]
    ]
];
