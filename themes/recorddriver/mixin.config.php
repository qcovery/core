<?php
return [
    'helpers' => [
        'factories' => [
            'RecordDriver\View\Helper\RecordDriver\Record' => 'RecordDriver\View\Helper\RecordDriver\RecordFactory',
            'RecordDriver\View\Helper\RecordDriver\SolrDetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetailsFactory',
        ],
        'aliases' => [
            'record' => 'RecordDriver\View\Helper\RecordDriver\Record',
            'solrDetails' => 'RecordDriver\View\Helper\RecordDriver\SolrDetails',
        ]
    ]
];
