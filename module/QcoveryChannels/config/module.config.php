<?php
namespace QcoveryChannels\Module\Configuration;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'channelprovider' => [
                'factories' => [
                    'QcoveryChannels\ChannelProvider\Topics' => 'QcoveryChannels\ChannelProvider\Factory::getTopics',
                ],
                'aliases' => [
                    'topics' => 'QcoveryChannels\ChannelProvider\Topics',
                ],
            ],
        ],
    ],
];

return $config;

