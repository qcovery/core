<?php
return [
    'css' => [
        'publicationsuggestion.css'
    ],
    'helpers' => [
        'factories' => [
            'PublicationSuggestion\View\Helper\PublicationSuggestion\PublicationSuggestion' => 'PublicationSuggestion\View\Helper\PublicationSuggestion\PublicationSuggestionFactory',
        ],
        'aliases' => [
            'PublicationSuggestion' => 'PublicationSuggestion\View\Helper\PublicationSuggestion\PublicationSuggestion',
        ]
    ]
];
