<?php
namespace ExtendedFeedback\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'ExtendedFeedback\Controller\FeedbackController' => 'VuFind\Controller\AbstractBaseFactory',
        ],
        'aliases' => [
            'Feedback' => 'ExtendedFeedback\Controller\FeedbackController',
            'feedback' => 'ExtendedFeedback\Controller\FeedbackController',
            'VuFind\Controller\FeedbackController' => 'ExtendedFeedback\Controller\FeedbackController',
        ],
    ],
];

return $config;
