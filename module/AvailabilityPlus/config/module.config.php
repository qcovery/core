<?php

return array (
  'controllers' => 
  array (
    'factories' => 
    array (
      'AvailabilityPlus\\Controller\\AvailabilityPlusController' => 'VuFind\\Controller\\AbstractBaseFactory',
    ),
    'aliases' => 
    array (
      'AvailabilityPlus' => 'AvailabilityPlus\\Controller\\AvailabilityPlusController',
      'availabilityplus' => 'AvailabilityPlus\\Controller\\AvailabilityPlusController',
    ),
  ),
  'router' => 
  array (
    'routes' => 
    array (
      'availabilityplus-home' => 
      array (
        'type' => 'Zend\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/AvailabilityPlus/Home',
          'defaults' => 
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'Home',
          ),
        ),
      ),
      'availabilityplus-testcases' => 
      array (
        'type' => 'Zend\\Router\\Http\\Literal',
        'options' => 
        array (
          'route' => '/AvailabilityPlus/TestCases',
          'defaults' => 
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'TestCases',
          ),
        ),
      ),
      'availabilityplus-debug' => 
      array (
        'type' => 'Zend\\Router\\Http\\Segment',
        'options' => 
        array (
          'route' => '/AvailabilityPlus/Debug/[:id]',
          'constraints' => 
          array (
            'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
            'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
            'id' => '[0-9xX]*',
          ),
          'defaults' => 
          array (
            'controller' => 'AvailabilityPlus',
            'action' => 'Debug',
          ),
        ),
      ),
    ),
  ),
);
