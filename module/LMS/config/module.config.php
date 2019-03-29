<?php

return array (
  'controllers' => 
  array (
    'factories' => 
    array (
      'LMS\\Controller\\CartController' => 'LMS\\Controller\\CartControllerFactory',
    ),
    'aliases' => 
    array (
      'VuFind\\Controller\\CartController' => 'LMS\\Controller\\CartController',
    ),
  ),
  'service_manager' => 
  array (
    'factories' => 
    array (
      'LMS\\Cart' => 'LMS\\CartFactory',
    ),
    'aliases' => 
    array (
      'VuFind\\Cart' => 'LMS\\Cart',
    ),
  ),
);