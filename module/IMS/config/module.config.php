<?php

return array (
  'controllers' => 
  array (
    'factories' => 
    array (
      'IMS\\Controller\\CartController' => 'IMS\\Controller\\CartControllerFactory',
    ),
    'aliases' => 
    array (
      'VuFind\\Controller\\CartController' => 'IMS\\Controller\\CartController',
    ),
  ),
  'service_manager' => 
  array (
    'factories' => 
    array (
      'IMS\\Cart' => 'IMS\\CartFactory',
    ),
    'aliases' => 
    array (
      'VuFind\\Cart' => 'IMS\\Cart',
    ),
  ),
);