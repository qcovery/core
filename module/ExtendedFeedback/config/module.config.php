<?php
namespace ExtendedFeedback\Module\Configuration;

$config = [
    'controllers' => [
        'factories' => [
            'ExtendedFeedback\Controller\FeedbackController' => 'VuFind\Controller\AbstractBaseFactory',
            'ExtendedFeedback\Controller\CartController' => 'ExtendedFeedback\Controller\CartControllerFactory',
        ],
        'aliases' => [
            'Feedback' => 'ExtendedFeedback\Controller\FeedbackController',
            'feedback' => 'ExtendedFeedback\Controller\FeedbackController',
            'VuFind\Controller\FeedbackController' => 'ExtendedFeedback\Controller\FeedbackController',
            'Cart' => 'ExtendedFeedback\Controller\CartController',
            'cart' => 'ExtendedFeedback\Controller\CartController',
            'VuFind\Controller\CartController' => 'ExtendedFeedback\Controller\CartController',
        ],
    ],
];

return $config;
