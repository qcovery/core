<?php

return array (
    'vufind' =>
        array (
            'plugin_managers' =>
                array (
                    'recorddriver' =>
                        array (
                            'factories' =>
                                array (
                                    'RecordDriver\\RecordDriver\\SolrMarc' => 'RecorDriver\RecordDriver\\SolrDefaultFactory',
                                ),
                            'aliases' =>
                                array (
                                    'VuFind\\RecordDriver\\SolrMarc' => 'RecordDriver\\RecordDriver\\SolrMarc',
                                ),
                            'delegators' =>
                                array (
                                    'RecorDriver\\RecordDriver\\SolrMarc' =>
                                        array (
                                            0 => 'VuFind\\RecordDriver\\IlsAwareDelegatorFactory',
                                        ),
                                    'VuFind\RecordDriver\\SolrMarcRemote' =>
                                        array (
                                            0 => 'VuFind\\RecordDriver\\IlsAwareDelegatorFactory',
                                        ),

                                ),
                        ),
                ),
        ),
);


