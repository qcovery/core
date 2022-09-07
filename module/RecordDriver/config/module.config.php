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
                                    'RecordDriver\\RecordDriver\\SolrMarc' => 'RecordDriver\\RecordDriver\\SolrDefaultFactory',
                                ),
                            'aliases' =>
                                array (
                                    'VuFind\\RecordDriver\\SolrMarc' => 'RecordDriver\\RecordDriver\\SolrMarc',
                                ),
                            'delegators' =>
                                array (
                                    'RecordDriver\\RecordDriver\\SolrMarc' =>
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


